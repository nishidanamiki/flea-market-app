<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddressRequest;
use App\Http\Requests\PurchaseRequest;
use App\Models\Item;
use App\Models\Address;
use App\Models\Purchase;
use App\Services\PurchaseService;
use App\Services\CheckoutReservationService;
use Illuminate\Support\Facades\Auth;
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

        if ($validated['payment'] === 'konbini') {
            abort(403, 'コンビニ決済は現在利用できません');
        }

        $reservationService = new CheckoutReservationService();

        try {
            $reservation = $reservationService->reserve(
                (int) $item->id,
                (int) auth()->id(),
                (int) $validated['address_id'],
                $validated['payment']
            );

            Stripe::setApiKey(config('services.stripe.secret'));

            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => 'jpy',
                            'unit_amount' => $item->price,
                            'product_data' => [
                                'name' => $item->name,
                            ],
                        ],
                        'quantity' => 1,
                    ]
                ],
                'mode' => 'payment',

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

        if ($stripeSession->payment_status !== 'paid') {
            return redirect()
                ->route('items.index')
                ->with('error', '決済が完了していません');
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

        // 購入確定処理をPurchaseServiceに任せる
        try {
            $purchaseService = new PurchaseService();

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
