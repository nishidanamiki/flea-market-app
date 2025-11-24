@extends('layouts.app')

@section('title', '決済完了 - COACHTECHフリマ')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/purchase/success.css') }}">
@endsection

@section('content')
    <section class="purchase-success">
        <h1>購入が完了しました！</h1>
        <p>ご購入ありがとうございました。</p>
        <a href="{{ route('items.index') }}" class="primary-button">商品一覧に戻る</a>
    </section>
@endsection
