<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddressRequest;
use App\Http\Requests\PurchaseRequest;
use App\Models\Item;
use App\Models\Address;
use App\Models\Purchase;
use Illuminate\Support\Facades\Auth;

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
        $user = Auth::user();
        $validated = $request->validated();
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

    public function store(PurchaseRequest $request, $item_id)
    {
        $validated = $request->validated();

        $item = Item::findOrFail($item_id);

        if ($item->is_sold) {
            return redirect()->route('items.index')->with('error', 'この商品は売り切れました');
        }

        Purchase::create([
            'user_id' => Auth::id(),
            'item_id' => $item_id,
            'payment_method' => $validated['payment'],
            'address_id' => $validated['address_id'],
            'status' => 'purchased',
        ]);

        $item->update(['is_sold' => true]);

        return redirect()->route('items.index');
    }
}
