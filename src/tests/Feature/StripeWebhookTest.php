<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use App\Models\Address;
use App\Models\CheckoutReservation;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_秘密鍵が未設定なら503を返す(): void
    {
        config(['services.stripe.webhook_secret' => null]);

        $request = Request::create(
            '/stripe/webhook',
            'POST',
            [],
            [],
            [],
            [],
            '{}'
        );

        $response = app(StripeWebhookController::class)->handle($request);

        $this->assertEquals(503, $response->getStatusCode());
    }

    public function test_不正な署名なら400を返す(): void
    {
        config(['services.stripe.webhook_secret' => 'whsec_test_secret']);

        $request = Request::create(
            '/stripe/webhook',
            'POST',
            [],
            [],
            [],
            ['HTTP_Stripe_Signature' => 'invalid'],
            '{}'
        );

        $response = app(StripeWebhookController::class)->handle($request);

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function test_正しい署名なら200を返す(): void
    {
        $secret = 'whsec_test_secret';

        config(['services.stripe.webhook_secret' => $secret]);

        $payload = json_encode([
            'id' => 'evt_test_123',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_123',
                    'object' => 'checkout.session',
                    'payment_status' => 'unpaid',
                ],
            ],
        ]);

        // Stripeからの通知を再現するため、テスト用の署名を生成する
        $timestamp = time();

        $signature = hash_hmac(
            'sha256',
            $timestamp . '.' . $payload,
            $secret
        );

        $signatureHeader = "t={$timestamp},v1={$signature}";

        $request = Request::create(
            '/stripe/webhook',
            'POST',
            [],
            [],
            [],
            ['HTTP_Stripe_Signature' => $signatureHeader],
            $payload
        );

        $response = app(StripeWebhookController::class)->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_決済完了Webhookで購入履歴が登録される(): void
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
            'stripe_session_id' => 'cs_test_webhook_001',
            'expires_at' => now()->addMinutes(30),
        ]);

        $secret = 'whsec_test_secret';
        config(['services.stripe.webhook_secret' => $secret]);

        $payload = json_encode([
            'id' => 'evt_test_paid_001',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_webhook_001',
                    'object' => 'checkout.session',
                    'payment_status' => 'paid',
                    'payment_intent' => 'pi_test_webhook_001',
                    'mode' => 'payment',
                    'currency' => 'jpy',
                    'amount_total' => 3000,
                    'metadata' => [
                        'item_id' => (string) $item->id,
                        'user_id' => (string) $buyer->id,
                        'address_id' => (string) $address->id,
                        'reservation_id' => (string) $reservation->id,
                    ],
                ],
            ],
        ]);

        $timestamp = time();

        $signature = hash_hmac(
            'sha256',
            $timestamp . '.' . $payload,
            $secret
        );

        $signatureHeader = "t={$timestamp},v1={$signature}";

        $response = $this->call(
            'POST',
            '/stripe/webhook',
            [],
            [],
            [],
            ['HTTP_Stripe_Signature' => $signatureHeader],
            $payload
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertDatabaseHas('purchases', [
            'user_id' => $buyer->id,
            'item_id' => $item->id,
            'address_id' => $address->id,
            'status' => 'paid',
            'stripe_session_id' => 'cs_test_webhook_001',
            'stripe_payment_intent_id' => 'pi_test_webhook_001',
        ]);

        $this->assertTrue($item->fresh()->is_sold);
    }

    public function test_WebhookのURLから不正な署名を拒否できる(): void
    {
        config([
            'services.stripe.webhook_secret' => 'whsec_test_secret',
        ]);

        $response = $this->postJson(
            '/stripe/webhook',
            [],
            ['Stripe-Signature' => 'invalid']
        );

        $response->assertStatus(400);
    }

    public function test_同じWebhookを2回受信しても購入履歴は1件だけ登録される(): void
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
            'stripe_session_id' => 'cs_test_webhook_duplicate_001',
            'expires_at' => now()->addMinutes(30),
        ]);

        $secret = 'whsec_test_secret';
        config(['services.stripe.webhook_secret' => $secret]);

        $payload = json_encode([
            'id' => 'evt_test_duplicate_001',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_webhook_duplicate_001',
                    'object' => 'checkout.session',
                    'payment_status' => 'paid',
                    'payment_intent' => 'pi_test_webhook_duplicate_001',
                    'mode' => 'payment',
                    'currency' => 'jpy',
                    'amount_total' => 3000,
                    'metadata' => [
                        'item_id' => (string) $item->id,
                        'user_id' => (string) $buyer->id,
                        'address_id' => (string) $address->id,
                        'reservation_id' => (string) $reservation->id,
                    ],
                ],
            ],
        ]);

        $timestamp = time();

        $signature = hash_hmac(
            'sha256',
            $timestamp . '.' . $payload,
            $secret
        );

        $signatureHeader = "t={$timestamp},v1={$signature}";

        $response1 = $this->call(
            'POST',
            '/stripe/webhook',
            [],
            [],
            [],
            ['HTTP_Stripe_Signature' => $signatureHeader],
            $payload
        );

        $response2 = $this->call(
            'POST',
            '/stripe/webhook',
            [],
            [],
            [],
            ['HTTP_Stripe_Signature' => $signatureHeader],
            $payload
        );

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        $this->assertDatabaseCount('purchases', 1);

        $this->assertTrue($item->fresh()->is_sold);
    }

    public function test_売却済み商品への別決済通知は500を返す(): void
    {
        $item = Item::factory()->create([
            'price' => 3000,
            'is_sold' => true,
        ]);

        $buyer = User::factory()->create();

        $address = Address::factory()->create([
            'user_id' => $buyer->id,
        ]);

        $secret = 'whsec_test_secret';
        config(['services.stripe.webhook_secret' => $secret]);

        $payload = json_encode([
            'id' => 'evt_test_sold_001',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_sold_001',
                    'payment_status' => 'paid',
                    'payment_intent' => 'pi_test_sold_001',
                    'mode' => 'payment',
                    'currency' => 'jpy',
                    'amount_total' => 3000,
                    'metadata' => [
                        'item_id' => (string) $item->id,
                        'user_id' => (string) $buyer->id,
                        'address_id' => (string) $address->id,
                    ],
                ],
            ],
        ]);

        $timestamp = time();

        $signature = hash_hmac(
            'sha256',
            $timestamp . '.' . $payload,
            $secret
        );

        $signatureHeader = "t={$timestamp},v1={$signature}";

        $response = $this->call(
            'POST',
            '/stripe/webhook',
            [],
            [],
            [],
            ['HTTP_Stripe_Signature' => $signatureHeader],
            $payload
        );

        $response->assertStatus(500);

        $this->assertDatabaseCount('purchases', 0);

        $this->assertTrue($item->fresh()->is_sold);
    }

    public function test_セッション期限切れWebhookで仮予約が期限切れになる(): void
    {
        $item = Item::factory()->create();
        $buyer = User::factory()->create();

        $reservation = CheckoutReservation::create([
            'item_id' => $item->id,
            'user_id' => $buyer->id,
            'status' => 'reserved',
            'stripe_session_id' => 'cs_test_expired_001',
            'expires_at' => now()->addMinutes(30),
        ]);

        $secret = 'whsec_test_secret';
        config(['services.stripe.webhook_secret' => $secret]);

        $payload = json_encode([
            'id' => 'evt_test_expired_001',
            'object' => 'event',
            'type' => 'checkout.session.expired',
            'data' => [
                'object' => [
                    'id' => 'cs_test_expired_001',
                    'object' => 'checkout.session',
                    'status' => 'expired',
                ],
            ],
        ]);

        $timestamp = time();

        $signature = hash_hmac(
            'sha256',
            $timestamp . '.' . $payload,
            $secret
        );

        $signatureHeader = "t={$timestamp},v1={$signature}";

        $this->call(
            'POST',
            '/stripe/webhook',
            [],
            [],
            [],
            ['HTTP_Stripe_Signature' => $signatureHeader],
            $payload
        )->assertStatus(200);

        $this->assertDatabaseHas('checkout_reservations', [
            'id' => $reservation->id,
            'status' => 'expired',
        ]);

        $this->assertFalse($item->fresh()->is_sold);
    }

    public function test_購入済み予約は期限切れWebhookで変更されない(): void
    {
        $item = Item::factory()->create([
            'is_sold' => true,
        ]);

        $buyer = User::factory()->create();

        $reservation = CheckoutReservation::create([
            'item_id' => $item->id,
            'user_id' => $buyer->id,
            'status' => 'completed',
            'stripe_session_id' => 'cs_test_completed_001',
            'expires_at' => now()->subMinutes(30),
        ]);

        $secret = 'whsec_test_secret';
        config(['services.stripe.webhook_secret' => $secret]);

        $payload = json_encode([
            'id' => 'evt_test_expired_completed_001',
            'object' => 'event',
            'type' => 'checkout.session.expired',
            'data' => [
                'object' => [
                    'id' => 'cs_test_completed_001',
                    'object' => 'checkout.session',
                    'status' => 'expired',
                ],
            ],
        ]);

        $timestamp = time();

        $signature = hash_hmac(
            'sha256',
            $timestamp . '.' . $payload,
            $secret
        );

        $signatureHeader = "t={$timestamp},v1={$signature}";

        $this->call(
            'POST',
            '/stripe/webhook',
            [],
            [],
            [],
            ['HTTP_Stripe_Signature' => $signatureHeader],
            $payload
        )->assertStatus(200);

        $this->assertDatabaseHas('checkout_reservations', [
            'id' => $reservation->id,
            'status' => 'completed',
        ]);

        $this->assertTrue($item->fresh()->is_sold);
    }

    public function test_古い期限切れWebhookは新しい予約を変更しない(): void
    {
        $item = Item::factory()->create();
        $buyer = User::factory()->create();

        $reservation = CheckoutReservation::create([
            'item_id' => $item->id,
            'user_id' => $buyer->id,
            'status' => 'reserved',
            'stripe_session_id' => 'cs_test_new_session',
            'expires_at' => now()->addMinutes(30),
        ]);

        $secret = 'whsec_test_secret';
        config(['services.stripe.webhook_secret'  => $secret]);

        $payload = json_encode([
            'id' => 'evt_test_old_expired',
            'object' => 'event',
            'type' => 'checkout.session.expired',
            'data' => [
                'object' => [
                    'id' => 'cs_test_old_session',
                    'object' => 'checkout.session',
                    'status' => 'expired',
                ],
            ],
        ]);

        $timestamp = time();

        $signature = hash_hmac(
            'sha256',
            $timestamp . '.' . $payload,
            $secret
        );

        $signatureHeader = "t={$timestamp},v1={$signature}";

        $this->call(
            'POST',
            '/stripe/webhook',
            [],
            [],
            [],
            ['HTTP_Stripe_Signature' => $signatureHeader],
            $payload
        )->assertStatus(200);

        $this->assertDatabaseHas('checkout_reservations', [
            'id' => $reservation->id,
            'status' => 'reserved',
            'stripe_session_id' => 'cs_test_new_session',
        ]);
    }
}
