<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddressRequest;
use App\Http\Requests\PurchaseRequest;
use App\Models\Item;
use App\Models\Address;
use App\Models\Purchase;
use App\Models\CheckoutReservation;
use App\Services\PurchaseService;
use App\Services\CheckoutReservationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function index($item_id)
    {
        $item = Item::findOrFail($item_id);

        if ($item->is_sold) {
            return redirect()->route('items.index')->with('error', 'この商品は売り切れました');
        }

        $address = Auth::user()->addresses()->latest()->first();
        return view('purchase.index', compact('item', 'address'));
    }

    public function editAddress($item_id)
    {
        $item = Item::findOrFail($item_id);
        $user = Auth::user();
        $address = Address::where('user_id', $user->id)->first();
        return view('purchase.address', compact('item', 'address'));
    }

    public function updateAddress(AddressRequest $request, $item_id)
    {
        $validated = $request->validated();
        $user = Auth::user();

        Address::create([
            'user_id' => $user->id,
            'postal_code' => $validated['postal_code'],
            'address' => $validated['address'],
            'building' => $validated['building'] ?? null,
        ]);

        return redirect()->route('purchase', ['item_id' => $item_id]);
    }

    // 既存テスト用の購入処理。本番環境では実行を禁止する
    public function store(PurchaseRequest $request, $item_id)
    {
        if (!app()->environment('testing')) {
            abort(403);
        }

        $validated = $request->validated();

        $item = Item::findOrFail($item_id);

        if ($item->is_sold) {
            return redirect()->route('items.index')->with('error', 'この商品は売り切れました');
        }

        Purchase::create([
            'user_id' => Auth::id(),
            'item_id' => $item->id,
            'payment_method' => $validated['payment'],
            'address_id' => $validated['address_id'],
            'status' => 'purchased',
        ]);

        $item->update(['is_sold' => true]);

        return redirect()->route('items.index');
    }

    public function checkout(PurchaseRequest $request, $item_id)
    {
        $validated = $request->validated();
        $item = Item::findOrFail($item_id);

        if ($item->is_sold) {
            return redirect()->route('items.index')->with('error', 'この商品は売り切れました');
        }

        $address = auth()->user()
            ->addresses()
            ->findOrFail($validated['address_id']);

        $reservationService = new CheckoutReservationService();

        // 同じ商品の決済準備が同時に進まないようにする
        $lock = Cache::lock('checkout_item_' . $item->id, 120);

        if (!$lock->get()) {
            return redirect()
                ->route('items.index')
                ->with('error', 'この商品は現在購入処理中です。しばらくしてからお試しください');
        }

        try {
            $sessionId = $reservationService->findActiveSessionId(
                (int) $item->id,
                (int) auth()->id(),
                (int) $validated['address_id'],
                $validated['payment']
            );

            if ($sessionId !== null) {
                Stripe::setApiKey(config('services.stripe.secret'));

                $existingSession = Session::retrieve($sessionId);

                $reservation = CheckoutReservation::where('item_id', $item->id)
                    ->where('stripe_session_id', $sessionId)
                    ->first();

                if (
                    $reservation !== null &&
                    (string) ($existingSession->metadata->reservation_id ?? '') === (string) $reservation->id &&
                    $existingSession->status === 'open' &&
                    (int) ($existingSession->expires_at ?? 0) > now()->timestamp &&
                    !empty($existingSession->url) &&
                    $existingSession->mode === 'payment' &&
                    $existingSession->currency === 'jpy' &&
                    (int) $existingSession->amount_total === (int) $item->price &&
                    (string) ($existingSession->metadata->item_id ?? '') === (string) $item->id &&
                    (string) ($existingSession->metadata->user_id ?? '') === (string) auth()->id() &&
                    (string) ($existingSession->metadata->address_id ?? '') === (string) $validated['address_id']
                ) {
                    return redirect()->away($existingSession->url);
                }

                throw new \RuntimeException('既存の決済セッションを再利用できません');
            }

            $reservation = $reservationService->reserve(
                (int) $item->id,
                (int) auth()->id(),
                (int) $validated['address_id'],
                $validated['payment']
            );

            Stripe::setApiKey(config('services.stripe.secret'));

            $session = Session::create([
                'payment_method_types' => [$reservation->payment_method],

                ...($reservation->payment_method === 'konbini'
                    ? [
                        'payment_method_options' => [
                            'konbini' => [
                                'expires_after_days' => 3,
                            ],
                        ],
                    ]
                    : []),

                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => 'jpy',
                            'unit_amount' => $reservation->amount,
                            'product_data' => [
                                'name' => $reservation->item_name,
                            ],
                        ],
                        'quantity' => 1,
                    ]
                ],
                'mode' => 'payment',
                'customer_email' => auth()->user()->email,

                'metadata' => [
                    'item_id' => $item->id,
                    'user_id' => auth()->id(),
                    'address_id' => $validated['address_id'],
                    'reservation_id' => (string) $reservation->id,
                ],
                'success_url' => route('purchase.success', ['item_id' => $item->id])
                    . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('purchase.cancel', ['item_id' => $item->id]),
            ], [
                'idempotency_key' => 'checkout_attempt_' . $reservation->attempt_key,
            ]);

            $reservationService->markAsReserved($reservation->id, $session);

            return redirect($session->url);
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('items.index')
                ->with('error', '決済の準備に失敗しました。しばらくしてからご確認ください');
        } finally {
            $lock->release();
        }
    }

    public function success(Request $request, $item_id)
    {
        $item = Item::findOrFail($item_id);

        $sessionId = $request->query('session_id');

        if (!$sessionId) {
            return redirect()
                ->route('items.index')
                ->with('error', '決済情報を確認できませんでした');
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $stripeSession = Session::retrieve($sessionId);
        } catch (\Exception $e) {
            return redirect()
                ->route('items.index')
                ->with('error', '決済情報を確認できませんでした');
        }

        if (
            $stripeSession->mode !== 'payment' ||
            $stripeSession->currency !== 'jpy' ||
            (int) $stripeSession->amount_total !== (int) $item->price
        ) {
            abort(403, '決済内容が商品情報と一致しません');
        }

        if (
            (int) $stripeSession->metadata->item_id !== (int) $item_id ||
            (int) $stripeSession->metadata->user_id !== (int) auth()->id()
        ) {
            abort(403);
        }

        $address_id = (int) $stripeSession->metadata->address_id;

        $address = auth()->user()->addresses()->find($address_id);

        if (!$address) {
            abort(403, '配送先住所を確認できません');
        }

        $reservation = CheckoutReservation::where('stripe_session_id', $stripeSession->id)
            ->where('item_id', $item->id)
            ->where('user_id', auth()->id())
            ->where('address_id', $address_id)
            ->first();

        if (
            $reservation === null ||
            (string) ($stripeSession->metadata->reservation_id ?? '') !== (string) $reservation->id
        ) {
            abort(403, '予約情報が決済情報と一致しません');
        }

        if (
            $reservation->payment_method === 'konbini' &&
            $reservation->status === 'reserved' &&
            $stripeSession->status === 'complete' &&
            $stripeSession->payment_status === 'unpaid'
        ) {
            return view('purchase.success', [
                'item' => $item,
                'status' => 'pending',
            ]);
        }

        if ($stripeSession->payment_status !== 'paid') {
            return redirect()
                ->route('items.index')
                ->with('error', '決済が完了していません');
        }

        // 購入確定処理をPurchaseServiceに任せる
        try {
            $purchaseService = app(PurchaseService::class);

            $purchaseService->completePaidCheckout($stripeSession);
        } catch (\RuntimeException $e) {
            return redirect()->route('items.index')->with('error', '購入確定処理を完了できませんでした');
        }


        return view('purchase.success', [
            'item' => $item,
            'status' => 'paid'
        ]);
    }

    public function cancel($item_id)
    {
        $item = Item::findOrFail($item_id);

        return view('purchase.cancel', compact('item'));
    }
}
