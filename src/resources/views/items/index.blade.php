@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/items/index.css') }}">
@endsection

@section('content')
    <div class="item-list-container">
        <div class="tab-menu">
            <a href="#" class="tab-group__item">おすすめ</a>
            <a href="#" class="tab-group__item">マイリスト</a>
        </div>

        {{-- <div class="item-list">
            @foreach ($items as $item)
                <div class="item-card">
                    <div class="item-card__img">
                        @if ($items->image)
                            <img src="{{ asset('storage/' . $item->image) }}" alt="{{ $item->name }}">
                    </div>
                </div>
        </div> --}}
    </div>
@endsection
