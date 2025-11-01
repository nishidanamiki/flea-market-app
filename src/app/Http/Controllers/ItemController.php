<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Item;
use App\Http\Requests\ExhibitionRequest;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab') ?? 'recommend';
        $keyword = $request->query('keyword');

        if ($tab === 'mylist') {
            if (auth()->check()) {
                $query = auth()->user()->likedItems();
            } else {
                $items = collect();
                return view('items.index', compact('items', 'tab', 'keyword'));
            }
        } else {
            $query = Item::query();
            if (auth()->check()) {
                $query->where('user_id', '!=', auth()->id());
            }
        }

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%");
            });
        }

        $items = $query->get();

        return view('items.index', compact('items', 'tab', 'keyword'));
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

    public function show($id)
    {
        $item = Item::with(['user', 'categories', 'comments.user'])->findOrFail($id);
        $liked = auth()->check()
            ? $item->likedUsers()->where('user_id', auth()->id())->exists()
            : false;
        return view('items.show', compact('item', 'liked'));
    }
}
