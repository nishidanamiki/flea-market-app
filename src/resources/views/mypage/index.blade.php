@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/mypage/index.css') }}">
@endsection

@section('content')
    <div class="mypage-container">
        <div class="mypage-top">
            <div class="mypage-img">
                <img src="{{ asset('storage/' . Auth::user()->profile_image_path) }}" alt="プロフィール画像">
            </div>
            <div class="mypage-info">
                <h1 class="profile-name">{{ Auth::user()->name }}</h1>
                <a href="{{ route('profile.edit') }}" class="edit-button">プロフィールを編集</a>
            </div>
        </div>
        <div class="tab-menu">
            <a href="{{ route('mypage', ['page' => 'sell']) }}"
                class="tab {{ $page === 'sell' ? 'active' : '' }}">出品した商品</a>
            <a href="{{ route('mypage', ['page' => 'buy']) }}" class="tab {{ $page === 'buy' ? 'active' : '' }}">購入した商品</a>
        </div>
        <div class="item-list">
            @foreach ($items as $item)
                <div class="item-card">
                    <div class="item-image">
                        <img src="{{ Str::startsWith($item->img_url, ['http://', 'https://']) ? $item->img_url : asset('storage/' . $item->img_url) }}"
                            alt="{{ $item->name }}">
                    </div>
                    <div class="item-info">
                        <p class="item-name">{{ $item->name }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
