@extends('layouts.app')

@php
    use Illuminate\Support\Str;
@endphp

@section('title', '商品一覧 - COACHTECHフリマ')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/items/index.css') }}">
@endsection

@section('content')
    <div class="item-list-container">
        <div class="tab-menu">
            <a href="{{ url('/') }}" class="tab {{ $tab === 'recommend' ? 'active' : '' }}">おすすめ</a>
            <a href="{{ route('items.index', ['tab' => 'mylist']) }}"
                class="tab {{ $tab === 'mylist' ? 'active' : '' }}">マイリスト</a>
        </div>

        @if ($tab === 'mylist' && !auth()->check())
        @else
            <div class="item-list">
                @foreach ($items as $item)
                    <div class="item-card">
                        <a href="{{ route('items.show', ['item_id' => $item->id]) }}">
                            <div class="item-image">
                                <img src="{{ Str::startsWith($item->img_url, ['http://', 'https://'])
                                    ? $item->img_url
                                    : asset('storage/' . $item->img_url) }}"
                                    alt="{{ $item->name }}">

                                @if ($item->is_sold)
                                    <span class="sold-label">SOLD</span>
                                @endif
                            </div>
                            <div class="item-info">
                                <p class="item-name">{{ $item->name }}</p>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
