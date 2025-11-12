@extends('layouts.app')

@section('title', 'メール認証確認ページ | COACHTECHフリマ')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/auth/verify-email.css') }}">
@endsection

@section('content')
    <div class="verify-container">
        <h1 class="visually-hidden">メール認証確認ページ</h1>
        <section class="verify-message-section">
            <p class="verify-message">
                登録していただいたメールアドレスに認証メールを送付しました。<br>メール認証を完了してください。
            </p>
        </section>
        <section class="verify-actions">
            <h2 class="visually-hidden">メール認証アクション</h2>
            <a href="http://localhost:8025" class="verify-button" target="_blank">認証はこちらから</a>
            <form action="{{ route('verification.send') }}" method="post">
                @csrf
                <button class="resend-link" type="submit">認証メールを再送する</button>
            </form>
        </section>
    </div>
@endsection
