@extends('layouts.app')

@section('title', $status === 'pending' ? 'お支払い待ち - COACHTECHフリマ' : '決済完了 - COACHTECHフリマ')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/purchase/success.css') }}">
@endsection

@section('content')
    <section class="purchase-success">
        @if ($status === 'pending')
            <h1>コンビニ決済のお支払い待ちです</h1>
            <p>お支払いが確認されるまで、購入は確定しません。</p>
            <p>Stripeから案内される支払い情報をご確認ください。</p>
        @else
            <h1>購入が完了しました！</h1>
            <p>ご購入ありがとうございました。</p>
        @endif

        <a href="{{ route('items.index') }}" class="primary-button">
            商品一覧に戻る
        </a>
    </section>
@endsection
