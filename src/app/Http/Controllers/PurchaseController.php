<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddressRequest;
use App\Http\Requests\PurchaseRequest;
use App\Models\Item;
use App\Models\Address;
use App\Models\Purchase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    //テスト時はStripeを使わずstoreを呼ぶ
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

    //本番用：Stripe決済処理
    public function checkout(PurchaseRequest $request, $item_id)
    {
        $validated = $request->validated();
        $item = Item::findOrFail($item_id);

        if ($item->is_sold) {
            return redirect()->route('items.index')->with('error', 'この商品は売り切れました');
        }

        $address = auth()->user()->addresses()->latest()->first();
        $address_id = optional($address)->id;

        if ($validated['payment'] === 'konbini') {
            abort(403, 'コンビニ決済は現在利用できません');
            Purchase::create([
                'user_id' => auth()->id(),
                'item_id' => $item_id,
                'payment_method' => 'konbini',
                'address_id' => $address_id,
                'status' => 'pending',
            ]);

            $item->update(['is_sold' => true]);

            Stripe::setApiKey(config('services.stripe.secret'));

            $session = Session::create([
                'payment_method_types' => ['konbini'],
                'payment_method_options' => [
                    'konbini' => [
                        'expires_after_days' => 3,
                    ]
                ],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'jpy',
                        'unit_amount' => $item->price,
                        'product_data' => [
                            'name' => $item->name,
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
            ]);

            return redirect($session->url);
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'jpy',
                    'unit_amount' => $item->price,
                    'product_data' => [
                        'name' => $item->name,
                    ],
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',

            'metadata' => [
                'item_id' => $item->id,
                'user_id' => auth()->id(),
                'address_id' => $validated['address_id'],
            ],
            'success_url' => route('purchase.success', ['item_id' => $item->id])
                . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('purchase.cancel', ['item_id' => $item->id]),
        ]);

        return redirect($session->url);
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

        DB::transaction(function () use ($item_id, $address_id) {
            // 対象の商品をロックして取得
            $item = Item::whereKey($item_id)->lockForUpdate()->firstOrFail();

            // すでに購入履歴がある場合は、重複登録を防ぐ
            if (Purchase::where('item_id', $item->id)->exists()) {
                abort(409, 'この商品はすでに購入されています');
            }

            // 売却済みの商品は購入できない
            if ($item->is_sold) {
                abort(409, 'この商品はすでに売り切れています');
            }

            // 購入履歴を登録
            Purchase::create([
                'user_id' => auth()->id(),
                'item_id' => $item->id,
                'payment_method' => 'card',
                'address_id' => $address_id,
                'status' => 'paid',
            ]);

            // 商品を売却済みに変更
            $item->update(['is_sold' => true]);
        });


        return view('purchase.success', [
            'item' => $item,
            'status' => 'paid'
        ]);
    }

    public function cancel(Request $request, Item $item)
    {
        return view('purchase.cancel', compact('item'));
    }
}
