@extends('layouts.app')

@section('title', '決済完了 - COACHTECHフリマ')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/purchase/success.css') }}">
@endsection

@section('content')
    <section class="purchase-success">
        @if ($status === 'paid')
            <h1>購入が完了しました！</h1>
            <p>ご購入ありがとうございました。</p>
        @elseif ($status === 'pending')
            <h1>注文を受け付けました</h1>
            <p>コンビニでのお支払いが完了すると注文が確定します。</p>
        @endif

        <a href="{{ route('items.index') }}" class="primary-button">商品一覧に戻る</a>
    </section>
@endsection
