@extends('layouts.app')

@section('title', 'プロフィール編集 - COACHTECHフリマ')

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
        <form class="profile-form" action="{{ route('profile.update') }}" method="post" enctype="multipart/form-data"
            novalidate>
            @csrf
            @method('PATCH')
            <div class="profile-image-wrapper">
                @if (auth()->user()->profile_image_path)
                    <img id="preview" class="profile-image"
                        src="{{ asset('storage/' . auth()->user()->profile_image_path) }}" alt="プロフィール画像" width="100">
                @else
                    <div id="preview" class="profile-image placeholder"></div>
                @endif
                <label for="profile_image_input" class="upload-button">画像を選択する</label>
                <input type="file" id="profile_image_input" name="profile_image" class="hidden-file-input"
                    accept="image/*">
                <div class="form__error">
                    @error('profile_image')
                        {{ $message }}
                    @enderror
                </div>
            </div>

            <div class="form-group">
                <label for="name">ユーザー名</label>
                <input type="text" id="name" name="name" value="{{ old('name', auth()->user()->name) }}"
                    required>
                <div class="form__error">
                    @error('name')
                        {{ $message }}
                    @enderror
                </div>
            </div>
            <div class="form-group">
                <label for="postal_code">郵便番号</label>
                <input type="text" id="postal_code" name="postal_code"
                    value="{{ old('postal_code', optional(auth()->user()->addresses->first())->postal_code ?? '') }}"
                    required>
                <div class="form__error">
                    @error('postal_code')
                        {{ $message }}
                    @enderror
                </div>
            </div>
            <div class="form-group">
                <label for="address">住所</label>
                <input type="text" id="address" name="address"
                    value="{{ old('address', optional(auth()->user()->addresses->first())->address ?? '') }}" required>
                <div class="form__error">
                    @error('address')
                        {{ $message }}
                    @enderror
                </div>
            </div>
            <div class="form-group">
                <label for="building">建物名</label>
                <input type="text" id="building" name="building"
                    value="{{ old('building', optional(auth()->user()->addresses->first())->building ?? '') }}">
            </div>
            <div class="form-button">
                <button type="submit" class="form-button__submit">更新する</button>
            </div>
        </form>
    </div>
@endsection

@section('script')
    <script>
        document.getElementById('profile_image_input')?.addEventListener('change', (e) => {
            const file = e.target.files?.[0];
            if (!file) return;
            const preview = document.getElementById('preview');
            const url = URL.createObjectURL(file);
            preview.innerHTML = '';
            if (preview.tagName === 'IMG') {
                preview.src = url;
            } else {
                const img = document.createElement('img');
                img.src = url;
                img.alt = 'preview';
                img.classList.add('profile-image');
                preview.replaceWith(img);
                img.id = 'preview';
            }
        });
    </script>
@endsection
