<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use App\Models\Address;
use App\Models\CheckoutReservation;
use App\Services\PurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\Checkout\Session;
use Tests\TestCase;

class PurchaseServiceFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_商品が存在しない場合は例外が発生する(): void
    {
        $session = $this->createStripeSession([
            'payment_status' => 'paid',
            'mode' => 'payment',
            'currency' => 'jpy',
            'metadata' => [
                'item_id' => '999999',
            ],
        ]);

        $service = new PurchaseService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('商品が見つかりません');

        $service->completePaidCheckout($session);
    }

    public function test_商品が存在する場合は購入履歴が登録される(): void
    {
        $item = Item::factory()->create();

        $buyer = User::factory()->create();

        $address = Address::factory()->create([
            'user_id' => $buyer->id,
        ]);

        $reservation = CheckoutReservation::create([
            'item_id' => $item->id,
            'user_id' => $buyer->id,
            'status' => 'reserved',
            'stripe_session_id' => 'cs_test_purchase_existing',
            'expires_at' => now()->addMinutes(30),
        ]);

        $session = $this->createStripeSession([
            'id' => 'cs_test_purchase_existing',
            'payment_status' => 'paid',
            'mode' => 'payment',
            'currency' => 'jpy',
            'amount_total' => $item->price,
            'metadata' => [
                'item_id' => (string) $item->id,
                'user_id' => (string) $buyer->id,
                'address_id' => (string) $address->id,
                'reservation_id' => (string) $reservation->id,
            ],
        ]);

        $service = new PurchaseService();

        $service->completePaidCheckout($session);

        $this->assertDatabaseHas('purchases', [
            'item_id' => $item->id,
            'user_id' => $buyer->id,
        ]);
    }

    public function test_正常な決済で購入履歴が登録され商品が売り切れになる(): void
    {
        $item = Item::factory()->create([
            'price' => 3000,
        ]);

        $buyer = User::factory()->create();

        $address = Address::factory()->create([
            'user_id' => $buyer->id,
        ]);

        $reservation = CheckoutReservation::create([
            'item_id' => $item->id,
            'user_id' => $buyer->id,
            'status' => 'reserved',
            'stripe_session_id' => 'cs_test_purchase_001',
            'expires_at' => now()->addMinutes(30),
        ]);

        $session = $this->createStripeSession([
            'id' => 'cs_test_purchase_001',
            'payment_status' => 'paid',
            'mode' => 'payment',
            'currency' => 'jpy',
            'amount_total' => 3000,
            'payment_intent' => 'pi_test_purchase_001',
            'metadata' => [
                'item_id' => (string) $item->id,
                'user_id' => (string) $buyer->id,
                'address_id' => (string) $address->id,
                'reservation_id' => (string) $reservation->id,
            ],
        ]);

        $service = new PurchaseService();
        $service->completePaidCheckout($session);

        $this->assertDatabaseHas('purchases', [
            'item_id' => $item->id,
            'user_id' => $buyer->id,
            'address_id' => $address->id,
            'payment_method' => 'card',
            'status' => 'paid',
            'stripe_session_id' => 'cs_test_purchase_001',
            'stripe_payment_intent_id' => 'pi_test_purchase_001',
        ]);

        $this->assertTrue($item->fresh()->is_sold);

        $this->assertDatabaseHas('checkout_reservations', [
            'id' => $reservation->id,
            'status' => 'completed',
        ]);
    }

    public function test_決済金額が商品価格と異なる場合は例外が発生する(): void
    {
        $item = Item::factory()->create([
            'price' => 3000,
        ]);

        $session = $this->createStripeSession([
            'payment_status' => 'paid',
            'mode' => 'payment',
            'currency' => 'jpy',
            'amount_total' => 1000,
            'metadata' => [
                'item_id' => (string) $item->id,
            ],
        ]);

        $service = new PurchaseService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('決済金額が商品価格と一致しません');

        $service->completePaidCheckout($session);
    }

    public function test_購入者IDがない場合は例外が発生する(): void
    {
        $item = Item::factory()->create([
            'price' => 3000,
        ]);

        $session = $this->createStripeSession([
            'payment_status' => 'paid',
            'mode' => 'payment',
            'currency' => 'jpy',
            'amount_total' => 3000,
            'metadata' => [
                'item_id' => (string) $item->id,
            ],
        ]);

        $service = new PurchaseService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('購入者IDが設定されていません');

        $service->completePaidCheckout($session);
    }

    public function test_購入者IDの形式が不正な場合は例外が発生する(): void
    {
        $item = Item::factory()->create([
            'price' => 3000,
        ]);

        $session = $this->createStripeSession([
            'payment_status' => 'paid',
            'mode' => 'payment',
            'currency' => 'jpy',
            'amount_total' => 3000,
            'metadata' => [
                'item_id' => (string) $item->id,
                'user_id' => 'abc',
            ],
        ]);

        $service = new PurchaseService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('購入者IDの形式が正しくありません');

        $service->completePaidCheckout($session);
    }

    public function test_購入者が存在しない場合は例外が発生する(): void
    {
        $item = Item::factory()->create([
            'price' => 3000,
        ]);

        $session = $this->createStripeSession([
            'payment_status' => 'paid',
            'mode' => 'payment',
            'currency' => 'jpy',
            'amount_total' => 3000,
            'metadata' => [
                'item_id' => (string) $item->id,
                'user_id' => '999999',
            ],
        ]);

        $service = new PurchaseService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('購入者が見つかりません');

        $service->completePaidCheckout($session);
    }

    public function test_出品者本人は自分の商品を購入できない(): void
    {
        $item = Item::factory()->create([
            'price' => 3000,
        ]);

        $session = $this->createStripeSession([
            'payment_status' => 'paid',
            'mode' => 'payment',
            'currency' => 'jpy',
            'amount_total' => 3000,
            'metadata' => [
                'item_id' => (string) $item->id,
                'user_id' => (string) $item->user_id,
            ],
        ]);

        $service = new PurchaseService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('自分の商品は購入できません');

        $service->completePaidCheckout($session);
    }

    public function test_配送先住所IDがない場合は例外が発生する(): void
    {
        $item = Item::factory()->create([
            'price' => 3000,
        ]);

        $buyer = User::factory()->create();

        $session = $this->createStripeSession([
            'payment_status' => 'paid',
            'mode' => 'payment',
            'currency' => 'jpy',
            'amount_total' => 3000,
            'metadata' => [
                'item_id' => (string) $item->id,
                'user_id' => (string) $buyer->id,
                // address_id はあえて設定しない
            ],
        ]);

        $service = new PurchaseService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('配送先住所IDが設定されていません');

        $service->completePaidCheckout($session);
    }

    public function test_配送先住所IDの形式が不正な場合は例外が発生する(): void
    {
        $item = Item::factory()->create([
            'price' => 3000,
        ]);

        $buyer = User::factory()->create();

        $session = $this->createStripeSession([
            'payment_status' => 'paid',
            'mode' => 'payment',
            'currency' => 'jpy',
            'amount_total' => 3000,
            'metadata' => [
                'item_id' => (string) $item->id,
                'user_id' => (string) $buyer->id,
                'address_id' => 'abc',
            ],
        ]);

        $service = new PurchaseService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('配送先住所IDの形式が正しくありません');

        $service->completePaidCheckout($session);
    }

    public function test_配送先住所が存在しない場合は例外が発生する(): void
    {
        $item = Item::factory()->create([
            'price' => 3000,
        ]);

        $buyer = User::factory()->create();

        $session = $this->createStripeSession([
            'payment_status' => 'paid',
            'mode' => 'payment',
            'currency' => 'jpy',
            'amount_total' => 3000,
            'metadata' => [
                'item_id' => (string) $item->id,
                'user_id' => (string) $buyer->id,
                'address_id' => '999999',
            ],
        ]);

        $service = new PurchaseService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('配送先住所が見つかりません');

        $service->completePaidCheckout($session);
    }

    public function test_他人の配送先住所は使用できない(): void
    {
        $item = Item::factory()->create([
            'price' => 3000,
        ]);

        $buyer = User::factory()->create();

        $otherUser = User::factory()->create();

        $address = Address::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $session = $this->createStripeSession([
            'payment_status' => 'paid',
            'mode' => 'payment',
            'currency' => 'jpy',
            'amount_total' => 3000,
            'metadata' => [
                'item_id' => (string) $item->id,
                'user_id' => (string) $buyer->id,
                'address_id' => (string) $address->id,
            ],
        ]);

        $service = new PurchaseService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('配送先住所が購入者のものではありません');

        $service->completePaidCheckout($session);
    }

    public function test_商品IDが極端に大きい場合は例外が発生する(): void
    {
        $session = $this->createStripeSession([
            'payment_status' => 'paid',
            'mode' => 'payment',
            'currency' => 'jpy',
            'metadata' => [
                'item_id' => '999999999999999999999999999999',
            ],
        ]);

        $service = new PurchaseService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('商品IDの形式が正しくありません');

        $service->completePaidCheckout($session);
     }

    public function test_同じ決済を2回処理しても購入履歴は1件だけ登録される(): void
    {
        $item = Item::factory()->create([
            'price' => 3000,
        ]);

        $buyer = User::factory()->create();

        $address = Address::factory()->create([
            'user_id' => $buyer->id,
        ]);

        $reservation = CheckoutReservation::create([
            'item_id' => $item->id,
            'user_id' => $buyer->id,
            'status' => 'reserved',
            'stripe_session_id' => 'cs_test_duplicate_001',
            'expires_at' => now()->addMinutes(30),
        ]);

        $session = $this->createStripeSession([
            'id' => 'cs_test_duplicate_001',
            'payment_status' => 'paid',
            'mode' => 'payment',
            'currency' => 'jpy',
            'amount_total' => 3000,
            'payment_intent' => 'pi_test_duplicate_001',
            'metadata' => [
                'item_id' => (string) $item->id,
                'user_id' => (string) $buyer->id,
                'address_id' => (string) $address->id,
                'reservation_id' => (string) $reservation->id,
            ],
        ]);

        $service = new PurchaseService();

        $service->completePaidCheckout($session);
        $service->completePaidCheckout($session);

        $this->assertDatabaseCount('purchases', 1);

        $this->assertTrue($item->fresh()->is_sold);
    }

    private function createStripeSession(array $attributes): Session
    {
        return Session::constructFrom(array_merge([
            'id' => 'cs_test_default_001',
            'payment_intent' => 'pi_test_default_001',
        ], $attributes));
    }
}
