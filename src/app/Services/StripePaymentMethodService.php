<?php

namespace App\Services;

use Stripe\StripeClient;
use Stripe\PaymentMethod;

class StripePaymentMethodService
{
    public function getType(string $paymentIntentId): string
    {
        $stripe = new StripeClient(config('services.stripe.secret'));

        $paymentIntent = $stripe->paymentIntents->retrieve(
            $paymentIntentId,
            ['expand' => ['payment_method']]
        );

        if ($paymentIntent->status !== 'succeeded') {
            throw new \RuntimeException('Stripeの決済が完了していません');
        }

        $paymentMethod = $paymentIntent->payment_method;

        if (!$paymentMethod instanceof PaymentMethod) {
            throw new \RuntimeException('Stripeの支払方法を取得できません');
        }

        return $paymentMethod->type;
    }
}
