<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommentRequest;
use App\Models\Item;
use App\Models\Comment;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function store(CommentRequest $request, Item $item)
    {
        Comment::create([
            'user_id' => Auth::id(),
            'item_id' => $item->id,
            'comment' => $request->input('comment'),
        ]);
        return back();
    }
}
