@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/profile/css') }}">
@endsection

@section('content')
    <div class="profile-container">
        <h1 class="page-title">プロフィール設定</h1>

        <div class="profile__img">
            <div class="profile-img--circle"></div>
            <label class="img-upload-button">
                画像を選択する
                <input type="file" accept="image/*" hidden>
            </label>
        </div>

        <form action="#" class="profile-form" method="post">
            @csrf
            <div class="form-group">
                <label for="name">ユーザー名</label>
                <input type="text" id="name" name="name">
            </div>
            <div class="form-group">
                <label for="postal">郵便番号</label>
                <input type="text" id="postal" name="postal">
            </div>
            <div class="form-group">
                <label for="address">住所</label>
                <input type="text" id="address" name="address">
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
