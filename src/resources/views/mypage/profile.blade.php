@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/mypage/profile.css') }}">
@endsection

@section('content')
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
    <div class="profile-container">
        <h1 class="page-title">プロフィール設定</h1>
        <form class="profile-form" action="{{ route('profile.update') }}" method="post" enctype="multipart/form-data">
            @csrf
            @method('PATCH')
            <div class="profile-image-wrapper">
                @if (auth()->user()->profile_image_path)
                    <img class="profile-image" src="{{ asset('storage/' . auth()->user()->profile_image_path) }}"
                        alt="プロフィール画像" width="100">
                @else
                    <div class="profile-image placeholder"></div>
                @endif
                <label for="profile_image" class="upload-button">画像を選択する</label>
                <input type="file" name="profile_image" id="profile_image" accept="image/*"
                    onchange="this.form.submit();">
                <div class="form__error">
                    @error('profile_image')
                        {{ $message }}
                    @enderror
                </div>
            </div>

            <div class="form-group">
                <label for="name">ユーザー名</label>
                <input type="text" id="name" name="name" required>
                <div class="form__error">
                    @error('name')
                        {{ $message }}
                    @enderror
                </div>
            </div>
            <div class="form-group">
                <label for="postal_code">郵便番号</label>
                <input type="text" id="postal_code" name="postal_code" required>
                <div class="form__error">
                    @error('postal_code')
                        {{ $message }}
                    @enderror
                </div>
            </div>
            <div class="form-group">
                <label for="address">住所</label>
                <input type="text" id="address" name="address" required>
                <div class="form__error">
                    @error('address')
                        {{ $message }}
                    @enderror
                </div>
            </div>
            <div class="form-group">
                <label for="building">建物名</label>
                <input type="text" id="building" name="building">
            </div>
            <div class="form-button">
                <button class="form-button__submit">更新する</button>
            </div>
        </form>
    </div>
@endsection
