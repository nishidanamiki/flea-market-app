<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use Illuminate\Support\Facades\Auth;

class LikeController extends Controller
{
    public function store(Item $item)
    {
        $user = Auth::user();
        if (! $item->likedUsers()->where('user_id', $user->id)->exists()) {
            $item->likedUsers()->attach($user->id);
        }
        return back();
    }

    public function destroy(Item $item)
    {
        $user = Auth::user();
        if ($item->likedUsers()->where('user_id', $user->id)->exists()) {
            $item->likedUsers()->detach($user->id);
        }
        return back();
    }
}
