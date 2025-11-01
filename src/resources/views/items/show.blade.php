@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/items/show.css') }}">
@endsection

@section('content')
    <div class="item-detail-wrapper">
        <div class="item-image-area">
            <img
                src="{{ Str::startsWith($item->img_url, ['http://', 'https://']) ? $item->img_url : asset('storage/' . $item->img_url) }}">
        </div>
        <div class="item-info-area">
            <h1 class="item-name">{{ $item->name }}</h1>
            <p class="item-brand">ブランド名　{{ $item->brand }}</p>
            <p class="item-price">&yen;<span>{{ number_format($item->price) }}</span>（税込）</p>
            <div class="like-section">
                <div class="icon-wrap">
                    @if (Auth::check())
                        @if ($item->likedUsers->contains(Auth::user()))
                            <form action="{{ route('items.unlike', $item) }}" method="post" class="like-form">
                                @csrf
                                @method('DELETE')
                                <button class="like-button liked" type="submit">
                                    <i class="fa-solid fa-star"></i>
                                </button>
                            </form>
                        @else
                            <form action="{{ route('items.like', $item) }}" method="post" class="like-form">
                                @csrf
                                <button class="like-button" type="submit">
                                    <i class="fa-regular fa-star"></i>
                                </button>
                            </form>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="like-button">
                            <i class="fa-regular fa-star"></i>
                        </a>
                    @endif
                    <span class="like-count">{{ $item->likedUsers->count() }}</span>
                </div>
                <div class="comment-icon">
                    <a href="#comments-section"><i class="fa-regular fa-comment"></i></a>
                    <span class="comment-count">{{ $item->comments->count() }}</span>
                </div>
            </div>
            <a href="{{ route('purchase', $item->id) }}" class="buy-button">購入手続きへ</a>
            <div class="section">
                <h2 class="description">商品説明</h2>
                <p>{{ $item->description }}</p>
            </div>
            <div class="section">
                <h2>商品の情報</h2>
                <div class="item-categories">
                    <h3>カテゴリー</h3>
                    @foreach ($item->categories as $category)
                        <span class="category-tag">{{ $category->name }}</span>
                    @endforeach
                </div>
                <div class="item-condition">
                    <h3>商品の状態</h3>
                    <p>{{ $item->condition }}</p>
                </div>
                <div class="section comments" id="comments-section">
                    <h2>コメント({{ $item->comments->count() }})</h2>
                    <div class="comment">
                        @foreach ($item->comments as $comment)
                            <div class="comment-user">
                                <img src="{{ $comment->user && $comment->user->profile_image_path ? asset('storage/' . $comment->user->profile_image_path) : asset('images/default-user.png') }}"
                                    alt="{{ $comment->user->name ?? '名無し' }}" class="user-icon">
                                <h3>{{ $comment->user->name }}</h3>
                            </div>
                            <p>{{ $comment->comment }}</p>
                        @endforeach
                    </div>
                    <form action="{{ route('comment.store', $item->id) }}" method="post" novalidate>
                        @csrf
                        <div class="comment-input">
                            <label for="comment">商品へのコメント</label>
                            <textarea name="comment" id="comment" required></textarea>
                            <div class="comment__error">
                                @error('comment')
                                    {{ $message }}
                                @enderror
                            </div>
                            <button class="comment-button" type="submit">コメントを送信する</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
