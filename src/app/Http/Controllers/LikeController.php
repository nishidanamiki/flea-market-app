<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\Like;
use Illuminate\Support\Facades\Auth;

class LikeController extends Controller
{
    public function store(Item $item)
    {
        $user = Auth::user();
        //「いいね」していない場合のみ登録
        if (! $item->likes()->where('user_id', $user->id)->exists()) {
            Like::create([
                'user_id' => $user->id,
                'item_id' => $item->id,
            ]);
        }
        return back();
    }

    public function destroy(Item $item)
    {
        $user = Auth::user();
        //既に「いいね」済なら削除
        $like = $item->likes()->where('user_id', $user->id)->first();
        if ($like) {
            $like->delete();
        }
        return back();
    }
}
