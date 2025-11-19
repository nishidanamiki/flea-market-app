@extends('layouts.app')

@section('title', '決済キャンセル - COACHTECHフリマ')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/purchase/cancel.css') }}">
@endsection

@section('content')
    <section class="purchase-cancel">
        <h1>決済がキャンセルされました</h1>
        <p>お手続きは完了していません。</p>
        <a href="{{ route('items.index') }}" class="primary-button">商品一覧へ戻る</a>
    </section>
@endsection
