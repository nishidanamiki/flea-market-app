@extends('layouts.app')

@section('title', '送付先変更 - COACHTECHフリマ')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/purchase/address.css') }}">
@endsection

@section('content')
    <div class="address-change-container">
        <h1 class="page-title">住所の変更</h1>
        <form action="{{ route('address.update', ['item_id' => $item->id]) }}" class="address-change" method="POST">
            @csrf
            <div class="form-group">
                <label for="postal_code">郵便番号</label>
                <input type="text" id="postal_code" name="postal_code"
                    value="{{ old('postal_code', $address->postal_code ?? '') }}">
                <div class="form__error">
                    @error('postal_code')
                        {{ $message }}
                    @enderror
                </div>
            </div>
            <div class="form-group">
                <label for="address">住所</label>
                <input type="text" id="address" name="address" value="{{ old('address', $address->address ?? '') }}">
                <div class="form__error">
                    @error('address')
                        {{ $message }}
                    @enderror
                </div>
            </div>
            <div class="form-group">
                <label for="building">建物名</label>
                <input type="text" id="building" name="building"
                    value="{{ old('building', $address->building ?? '') }}">
            </div>
            <div class="update-button">
                <button class="update-button__submit" type="submit">更新する</button>
            </div>
        </form>
    </div>
@endsection
