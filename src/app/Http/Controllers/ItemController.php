<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Item;
use App\Http\Requests\ExhibitionRequest;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function index()
    {
        $items = Item::latest()->get();
        return view('items.index', compact('items'));
    }

    public function create()
    {
        $categories = Category::all();
        return view('items.sell', compact('categories'));
    }

    public function store(ExhibitionRequest $request)
    {
        $validated = $request->validated();
        if ($request->hasFile('img_url')) {
            $path = $request->file('img_url')->store('item_images', 'public');
            $validated['img_url'] = $path;
        }

        $validated['user_id'] = auth() ->id();
        $item = Item::create($validated);
        if ($request->filled('categories')) {
            $item->categories()->attach($request->input('categories'));
        }
        return redirect()->route('items.index');
    }
}
