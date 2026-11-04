<?php

namespace Tests\Unit;

use App\Services\PurchaseService;
use PHPUnit\Framework\TestCase;
use Stripe\Checkout\Session;

class PurchaseServiceTest extends TestCase
{
    public function test_未払いの場合は例外が発生する(): void
    {
        $session = Session::constructFrom([
            'payment_status' => 'unpaid',
            'mode' => 'payment',
            'currency' => 'jpy',
        ]);

        $service = new PurchaseService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('決済が完了していません');

        $service->completePaidCheckout($session);
    }

    public function test_決済モードが異なる場合は例外が発生する(): void
    {
        $session = Session::constructFrom([
            'payment_status' => 'paid',
            'mode' => 'subscription',
            'currency' => 'jpy',
        ]);

        $service = new PurchaseService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('決済モードが正しくありません');

        $service->completePaidCheckout($session);
    }

    public function test_通貨が日本円以外の場合は例外が発生する(): void
    {
        $session = Session::constructFrom([
            'payment_status' => 'paid',
            'mode' => 'payment',
            'currency' => 'usd',
        ]);

        $service = new PurchaseService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('決済通貨が正しくありません');

        $service->completePaidCheckout($session);
    }

    public function test_商品IDがない場合は例外が発生する(): void
    {
        $session = Session::constructFrom([
            'id' => 'cs_test_missing_item_001',
            'payment_intent' => 'pi_test_missing_item_001',
            'payment_status' => 'paid',
            'mode' => 'payment',
            'currency' => 'jpy',
            'metadata' => [],
        ]);

        $service = new PurchaseService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('商品IDが設定されていません');

        $service->completePaidCheckout($session);
    }

    public function test_商品IDの形式が不正な場合は例外が発生する(): void
    {
        $session = Session::constructFrom([
            'id' => 'cs_test_invalid_item_001',
            'payment_intent' => 'pi_test_invalid_item_001',
            'payment_status' => 'paid',
            'mode' => 'payment',
            'currency' => 'jpy',
            'metadata' => [
                'item_id' => 'abc',
            ],
        ]);

        $service = new PurchaseService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('商品IDの形式が正しくありません');

        $service->completePaidCheckout($session);
    }
}
