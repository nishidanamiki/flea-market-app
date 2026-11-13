<?php

namespace App\Http\Controllers;

use App\Services\PurchaseService;
use App\Models\CheckoutReservation;
use Illuminate\Http\Request;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->getContent();

        $signature = $request->header('Stripe-Signature');

        $secret = config('services.stripe.webhook_secret');

        if (!$secret) {
            return response()->json(['error' => 'Webhook設定エラー'], 503);
        }

        try {
            // Stripeの署名を検証し、偽装された通知を拒否する
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                $secret
            );
        } catch (\UnexpectedValueException $e) {
            return response()->json(['error' => '不正なデータ'], 400);
        } catch (SignatureVerificationException $e) {
            return response()->json(['error' => '署名検証失敗'], 400);
        }

        if (in_array($event->type, [
            'checkout.session.completed',
            'checkout.session.async_payment_succeeded',
        ], true)) {
            $stripeSession = $event->data->object;

            if ($stripeSession->payment_status === 'paid') {
                try {
                    $purchaseService = app(PurchaseService::class);
                    $purchaseService->completePaidCheckout($stripeSession);
                } catch (\Throwable $e) {
                    report($e);

                    return response()->json([
                        'error' => '購入確定処理に失敗しました',
                    ], 500);
                }
            }
        }

        if ($event->type === 'checkout.session.async_payment_failed') {
            $stripeSession = $event->data->object;

            CheckoutReservation::where(
                'stripe_session_id',
                $stripeSession->id
            )
                ->where('payment_method', 'konbini')
                ->where('status', 'reserved')
                ->update(['status' => 'expired']);
        }

        if ($event->type === 'checkout.session.expired') {
            $stripeSession = $event->data->object;

            CheckoutReservation::where(
                'stripe_session_id',
                $stripeSession->id
            )
                ->where('status', 'reserved')
                ->update(['status' => 'expired']);
        }

        return response()->json(['received' => true]);
    }
}
