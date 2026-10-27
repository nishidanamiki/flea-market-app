<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Stripeから送られた通知の内容を取得
        $payload = $request->getContent();

        // Stripeが付けた署名を取得
        $signature = $request->header('Stripe-Signature');

        // Webhook署名検証用の秘密鍵を取得
        $secret = config('services.stripe.webhook_secret');

        // 秘密鍵が設定されていない場合は処理しない
        if (!$secret) {
            return response()->json(['error' => 'Webhook設定エラー'], 503);
        }

        try {
            // 通知が本当にStripeから届いたか検証
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                $secret
            );
        } catch (\UnexpectedValueException $e) {
            // 通知データの形式が不正
            return response()->json(['error' => '不正なデータ'], 400);
        } catch (SignatureVerificationException $e) {
            // 署名が一致しない
            return response()->json(['error' => '署名検証失敗'], 400);
        }

        // 現段階では署名検証のみ行う
        return response()->json(['received' => true]);
    }
}
