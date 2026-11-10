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

        if ($event->type === 'checkout.session.completed') {
            $stripeSession = $event->data->object;

            if ($stripeSession->payment_status === 'paid') {
                try {
                    $purchaseService = new PurchaseService();
                    $purchaseService->completePaidCheckout($stripeSession);
                } catch (\Throwable $e) {
                    report($e);

                    // 購入確定に失敗した場合は成功を返さず、Stripeの再送対象にする
                    return response()->json([
                        'error' => '購入確定処理に失敗しました',
                    ], 500);
                }
            }
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
