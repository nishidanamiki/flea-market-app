<?php

namespace Tests\Feature;

use App\Http\Controllers\StripeWebhookController;
use Illuminate\Http\Request;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
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
}
