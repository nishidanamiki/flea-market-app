@extends('layouts.app')

@php
    use Illuminate\Support\Str;
@endphp

@section('title', '商品一覧 - COACHTECHフリマ')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/items/index.css') }}">
@endsection

@section('content')
    <section class="item-list-container">
        <h1 class="visually-hidden">商品一覧</h1>
        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif
        <nav class="tab-menu">
            <a href="{{ route('items.index', ['tab' => 'recommend', 'keyword' => request('keyword')]) }}"
                class="tab {{ $tab === 'recommend' ? 'active' : '' }}">おすすめ</a>
            <a href="{{ route('items.index', ['tab' => 'mylist', 'keyword' => request('keyword')]) }}"
                class="tab {{ $tab === 'mylist' ? 'active' : '' }}">マイリスト</a>
        </nav>

        @if ($tab === 'mylist' && !auth()->check())
        @else
            <section class="item-list">
                <h2 class="visually-hidden">
                    {{ $tab === 'recommend' ? 'おすすめ商品一覧' : 'マイリストの商品一覧' }}
                </h2>
                @foreach ($items as $item)
                    <article class="item-card">
                        <a href="{{ route('items.show', ['item_id' => $item->id]) }}">
                            <div class="item-image">
                                <img src="{{ Str::startsWith($item->img_url, ['http://', 'https://'])
                                    ? $item->img_url
                                    : asset('storage/' . $item->img_url) }}"
                                    alt="{{ $item->name }}">
                                @if ($item->isSold())
                                    <span class="sold-label">SOLD</span>
                                @endif
                            </div>
                            <div class="item-info">
                                <h3 class="item-name">{{ $item->name }}</h3>
                            </div>
                        </a>
                    </article>
                @endforeach
            </section>
        @endif
    </section>
@endsection
