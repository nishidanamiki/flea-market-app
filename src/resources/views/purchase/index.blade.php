@extends('layouts.app')

@section('title', '購入内容の確認 - COACHTECHフリマ')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/purchase/index.css') }}">
@endsection

@section('content')
    <div class="purchase-container">
        <h1 class="visually-hidden">購入内容の確認</h1>
        <form action="{{ route('purchase.store', ['item_id' => $item->id]) }}" class="purchase-form" method="POST" novalidate>
            @csrf
            <div class="purchase-flex">
                <div class="purchase-left">
                    <section class="item-info">
                        <div class="item-content">
                            <div class="item-image">
                                <img src="{{ Str::startsWith($item->img_url, ['http://', 'https://']) ? $item->img_url : asset('storage/' . $item->img_url) }}"
                                    alt="{{ $item->name }}">
                            </div>
                            <div class="item-detail">
                                <p class="item-name">{{ $item->name }}</p>
                                <p class="item-price">&yen;<span>{{ number_format($item->price) }}</span></p>
                            </div>
                        </div>
                    </section>
                    <section class="payment-method">
                        <h2>支払い方法</h2>
                        <label for="payment" class="visually-hidden">支払い方法を選択してください</label>
                        <select name="payment" id="payment" required>
                            <option value="" disabled selected hidden>選択してください</option>
                            <option value="convenience" {{ old('payment') == 'convenience' ? 'selected' : '' }}>コンビニ払い
                            </option>
                            <option value="credit" {{ old('payment') == 'credit' ? 'selected' : '' }}>カード払い</option>
                        </select>
                        <div class="form__error">
                            @error('payment')
                                {{ $message }}
                            @enderror
                        </div>
                    </section>
                    <section class="shipping-address">
                        <div class="address-header">
                            <h2>配送先</h2>
                            <a href="{{ route('address.edit', ['item_id' => $item->id]) }}" class="address-edit">変更する</a>
                        </div>
                        <div class="address-area">
                            @if ($address)
                                <p class="postal-code">〒{{ $address->postal_code }}</p>
                                <p class="address">{{ $address->address }}{{ $address->building }}</p>
                                <input type="hidden" name="address_id" value="{{ $address->id }}">
                            @else
                                <p class="address">住所が登録されていません</p>
                            @endif
                            <div class="form__error">
                                @error('address_id')
                                    {{ $message }}
                                @enderror
                            </div>
                        </div>
                    </section>
                </div>
                <aside class="purchase-summary">
                    <div class="summary-box">
                        <div class="summary-row">
                            <span class="label">商品代金</span>
                            <span class="value">&yen;{{ number_format($item->price) }}</span>
                        </div>
                        <div class="summary-row">
                            <span class="label">支払い方法</span>
                            <span class="value" id="selected-payment">選択されていません</span>
                        </div>
                    </div>
                    <button class="purchase-button" type="submit">購入する</button>
                </aside>
            </div>
        </form>
    </div>
@endsection

@section('script')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const selectPayment = document.getElementById('payment');
            const displayPayment = document.getElementById('selected-payment');

            selectPayment.addEventListener('change', (e) => {
                const selectedValue = e.target.value;

                if (selectedValue === 'convenience') {
                    displayPayment.textContent = 'コンビニ払い';
                } else if (selectedValue === 'credit') {
                    displayPayment.textContent = 'カード払い';
                } else {
                    displayPayment.textContent = '選択されていません';
                }
            });
        });
    </script>
@endsection
