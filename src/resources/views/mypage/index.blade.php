@extends('layouts.app')

@section('title', 'マイページ - COACHTECHフリマ')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/mypage/index.css') }}">
@endsection

@section('content')
    <div class="mypage-container">
        <h1 class="visually-hidden">マイページ</h1>
        <div class="mypage-top">
            <div class="mypage-img">
                @if (Auth::user()->profile_image_path && file_exists(public_path('storage/' . Auth::user()->profile_image_path)))
                    <img src="{{ asset('storage/' . Auth::user()->profile_image_path) }}"
                        alt="{{ Auth::user()->name }}のプロフィール画像">
                @else
                    <div class="profile-placeholder">No Image</div>
                @endif
            </div>
            <div class="mypage-info">
                <h2 class="profile-name">{{ Auth::user()->name }}</h2>
                <a href="{{ route('profile.edit') }}" class="edit-button">プロフィールを編集</a>
            </div>
        </div>
        <nav class="tab-menu">
            <a href="{{ route('mypage', ['page' => 'sell']) }}"
                class="tab {{ $page === 'sell' ? 'active' : '' }}">出品した商品</a>
            <a href="{{ route('mypage', ['page' => 'buy']) }}" class="tab {{ $page === 'buy' ? 'active' : '' }}">購入した商品</a>
        </nav>
        <div class="item-list">
            <h2 class="visually-hidden">
                {{ $page === 'sell' ? '出品した商品一覧' : '購入した商品一覧' }}
            </h2>
            @foreach ($items as $item)
                @if ($page === 'buy')
                    @php
                        $item = $item->item;
                    @endphp
                @endif
                @if ($item)
                    <article class="item-card">
                        <div class="item-image">
                            <img src="{{ \Illuminate\Support\Str::startsWith($item->img_url, ['http://', 'https://']) ? $item->img_url : asset('storage/' . $item->img_url) }}"
                                alt="{{ $item->name }}の画像">
                        </div>
                        <div class="item-info">
                            <h3 class="item-name">{{ $item->name }}</h3>
                        </div>
                    </article>
                @endif
            @endforeach
        </div>
    </div>
@endsection
