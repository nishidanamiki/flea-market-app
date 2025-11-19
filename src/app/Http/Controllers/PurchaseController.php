<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddressRequest;
use App\Http\Requests\PurchaseRequest;
use App\Models\Item;
use App\Models\Address;
use App\Models\Purchase;
use Illuminate\Support\Facades\Auth;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Symfony\Component\HttpFoundation\Request;

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
        Address::updateOrCreate(
            ['user_id' => $user->id],
            [
                'postal_code' => $validated['postal_code'],
                'address' => $validated['address'],
                'building' => $validated['building'] ?? null,
            ]
        );
        return redirect()->route('purchase', ['item_id' => $item_id]);
    }

    //テスト時はStripeを使わずstoreを呼ぶ
    public function store(PurchaseRequest $request, $item_id)
    {
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
                'success_url' => route('purchase.success', ['item' > $item->id]),
                'cancel_url' => route('purchase.cancel', ['item' => $item->id]),
            ]);

            return redirect($session->url);
        }

        session()->put('payment_method', 'card');

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
            'success_url' => route('purchase.success', ['item' => $item->id]),
            'cancel_url' => route('purchase.cancel', ['item' => $item->id]),
        ]);

        return redirect($session->url);
    }

    public function success(Request $request, $item_id)
    {
        $item = Item::findOrFail($item_id);

        if ($item->is_sold && !session()->has('payment_method')) {
            return view('purchase.success', [
                'item' => $item,
                'status' => 'pending'
            ]);
        }

        $payment = session()->get('payment_method');

        if ($payment === 'card') {
            $address = auth()->user()->addresses()->latest()->first();
            $address_id = optional($address)->id;

            Purchase::create([
                'user_id' => auth()->id(),
                'item_id' => $item_id,
                'payment_method' => 'card',
                'address_id' => $address_id,
                'status' => 'paid',
            ]);

            $item->update(['is_sold' => true]);

            session()->forget('payment_method');

            return view('purchase.success', [
                'item' => $item,
                'status' => 'paid'
            ]);
        }
        return view('purchase.success', [
            'item' => $item,
            'status' => 'pending'
        ]);
    }

    public function cancel(Request $request, Item $item)
    {
        return view('purchase.cancel', compact('item'));
    }
}
