@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/items/sell.css') }}">
@endsection

@section('content')
    <div class="sell-container">
        <h1 class="page-title">商品の出品</h1>
        <form action="{{ route('items.store') }}" method="post" enctype="multipart/form-data" novalidate>
            @csrf
            <div class="form-group">
                <label for="img_url" class="form-label">商品画像</label>
                <div class="image-upload-box">
                    <input type="file" name="img_url" id="img_url" class="file-input" required>
                    <label for="img_url" class="upload-label">画像を選択する</label>
                    <div id="itempreview" class="image-preview"></div>
                </div>
                <div class="form__error">
                    @error('img_url')
                        {{ $message }}
                    @enderror
                </div>
            </div>
            <h2 class="sub-title">商品の詳細</h2>
            <div class="form-group">
                <label for="category" class="form-label">カテゴリー</label>
                <div class="category-tags">
                    @foreach ($categories as $category)
                        <label class="category-label">
                            <input type="checkbox" name="categories[]" value="{{ $category->id }}">
                            <span>{{ $category->name }}</span>
                        </label>
                    @endforeach
                </div>
                <div class="form__error">
                    @error('categories')
                        {{ $message }}
                    @enderror
                </div>
            </div>
            <div class="form-group">
                <label for="condition" class="form-label">商品の状態</label>
                <div class="select-wrap">
                    <select name="condition" id="condition" required>
                        <option value="">選択してください</option>
                        <option value="良好">良好</option>
                        <option value="目立った傷や汚れなし">目立った傷や汚れなし</option>
                        <option value="やや傷や汚れあり">やや傷や汚れあり</option>
                        <option value="状態が悪い">状態が悪い</option>
                    </select>
                </div>
                <div class="form__error">
                    @error('condition')
                        {{ $message }}
                    @enderror
                </div>
            </div>
            <h2 class="sub-title">商品名と説明</h2>
            <div class="form-group">
                <label for="name" class="form-label">商品名</label>
                <input type="text" name="name" id="name" required>
                <div class="form__error">
                    @error('name')
                        {{ $message }}
                    @enderror
                </div>
            </div>
            <div class="form-group">
                <label for="brand" class="form-label">ブランド名</label>
                <input type="text" name="brand" id="brand">
            </div>
            <div class="form-group">
                <label for="description" class="form-label">商品の説明</label>
                <textarea name="description" id="description" rows="4" required></textarea>
                <div class="form__error">
                    @error('description')
                        {{ $message }}
                    @enderror
                </div>
            </div>
            <div class="form-group">
                <label for="price" class="form-label">販売価格</label>
                <div class="price-input">
                    <span class="yen-mark">&yen</span>
                    <input type="number" name="price" id="price" required>
                </div>
                <div class="form__error">
                    @error('price')
                        {{ $message }}
                    @enderror
                </div>
            </div>
            <button class="form-button">出品する</button>
        </form>
    </div>
@endsection

@section('script')
    <script>
        document.getElementById('img_url')?.addEventListener('change', (e) => {
            const file = e.target.files?.[0];
            if (!file) return;
            const preview = document.getElementById('itempreview');
            const uploadLabel = document.querySelector('.upload-label');
            const url = URL.createObjectURL(file);
            preview.innerHTML = '';
            const img = document.createElement('img');
            img.src = url;
            img.alt = 'preview';
            img.classList.add('preview-image');
            preview.appendChild(img);
            uploadLabel.style.display = 'none';
        });
    </script>
@endsection
