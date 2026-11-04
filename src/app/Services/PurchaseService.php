<?php

namespace App\Services;

use App\Models\Item;
use App\Models\User;
use App\Models\Address;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;
use Stripe\Checkout\Session;

class PurchaseService
{
    public function completePaidCheckout(Session $stripeSession): void
    {
        if ($stripeSession->payment_status !== 'paid') {
            throw new \RuntimeException('決済が完了していません');
        }

        if ($stripeSession->mode !== 'payment') {
            throw new \RuntimeException('決済モードが正しくありません');
        }

        if ($stripeSession->currency !== 'jpy') {
            throw new \RuntimeException('決済通貨が正しくありません');
        }

        if (empty($stripeSession->id)) {
            throw new \RuntimeException('StripeセッションIDが設定されていません');
        }

        if (
            !is_string($stripeSession->payment_intent) ||
            $stripeSession->payment_intent === ''
        ) {
            throw new \RuntimeException('Stripe決済IDが設定されていません');
        }

        if (empty($stripeSession->metadata->item_id)) {
            throw new \RuntimeException('商品IDが設定されていません');
        }

        $itemId = $this->validateId(
            $stripeSession->metadata->item_id,
            '商品ID'
        );

        $item = $this->findItem($itemId);

        if ($stripeSession->amount_total !== (int) $item->price) {
            throw new \RuntimeException('決済金額が商品価格と一致しません');
        }

        if (empty($stripeSession->metadata->user_id)) {
            throw new \RuntimeException('購入者IDが設定されていません');
        }

        $userId = $this->validateId(
            $stripeSession->metadata->user_id,
            '購入者ID'
        );

        $user = User::find($userId);

        if ($user === null) {
            throw new \RuntimeException('購入者が見つかりません');
        }

        if ((int) $item->user_id === (int) $user->id) {
            throw new \RuntimeException('自分の商品は購入できません');
        }

        if (empty($stripeSession->metadata->address_id)) {
            throw new \RuntimeException('配送先住所IDが設定されていません');
        }

        $addressId = $this->validateId(
            $stripeSession->metadata->address_id,
            '配送先住所ID'
        );

        $address = Address::find($addressId);

        if ($address === null) {
            throw new \RuntimeException('配送先住所が見つかりません');
        }

        if ((int) $address->user_id !== (int) $user->id) {
            throw new \RuntimeException('配送先住所が購入者のものではありません');
        }

        // 購入履歴の登録と商品の売却状態の更新をまとめて実行する
        DB::transaction(function () use ($itemId, $userId, $addressId, $stripeSession) {
            // 同じ商品の購入確定処理が並行して進まないようにロックする
            $lockedItem = Item::whereKey($itemId)
                ->lockForUpdate()
                ->firstOrFail();

            // Stripeから同じ決済通知が再送された場合に備える
            $existingPurchase = Purchase::where(
                'stripe_session_id',
                $stripeSession->id
            )->first();

            if ($existingPurchase !== null) {
                if (
                    (int) $existingPurchase->item_id !== $itemId ||
                    (int) $existingPurchase->user_id !== $userId ||
                    (int) $existingPurchase->address_id !== $addressId ||
                    $existingPurchase->stripe_payment_intent_id !== $stripeSession->payment_intent ||
                    $existingPurchase->status !== 'paid'
                ) {
                    throw new \RuntimeException('登録済みの購入情報と決済情報が一致しません');
                }
                // 同じ決済は再登録せずに正常終了する
                return;
            }

            if ($lockedItem->is_sold || Purchase::where('item_id', $lockedItem->id)->exists()) {
                throw new \RuntimeException('この商品はすでに売り切れです');
            }

            // 商品の価格が変更されていないか、ロック取得後に再確認する
            if ($stripeSession->amount_total !== (int) $lockedItem->price) {
                throw new \RuntimeException('決済金額が商品価格と一致しません');
            }

            if (Purchase::where('stripe_payment_intent_id', $stripeSession->payment_intent)->exists()) {
                throw new \RuntimeException('Stripe決済IDはすでに登録されています');
            }

            Purchase::create([
                'user_id' => $userId,
                'item_id' => $lockedItem->id,
                'address_id' => $addressId,
                'payment_method' => 'card',
                'status' => 'paid',
                'stripe_session_id' => $stripeSession->id,
                'stripe_payment_intent_id' => $stripeSession->payment_intent,
            ]);

            $lockedItem->update([
                'is_sold' => true,
            ]);
        });
    }

    private function findItem(int $itemId): Item
    {
        $item = Item::find($itemId);

        if ($item === null) {
            throw new \RuntimeException('商品が見つかりません');
        }

        return $item;
    }

    private function validateId($value, string $name): int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => [
                'min_range' => 1,
            ],
        ]);

        if ($id === false) {
            throw new \RuntimeException($name . 'の形式が正しくありません');
        }

        return $id;
    }
}
