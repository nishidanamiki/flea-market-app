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
            <a href="#" class="tab active">出品した商品</a>
            <a href="#" class="tab">購入した商品</a>
        </div>
    </div>
@endsection
