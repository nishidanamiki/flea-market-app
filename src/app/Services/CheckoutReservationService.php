<?php

namespace App\Services;

use App\Models\Item;
use App\Models\CheckoutReservation;
use Illuminate\Support\Facades\DB;
use Stripe\Checkout\Session;
use Carbon\Carbon;

class CheckoutReservationService
{
    public function reserve(int $itemId, int $userId): CheckoutReservation
    {
        return DB::transaction(function () use ($itemId, $userId) {
            $item = Item::whereKey($itemId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($item->is_sold) {
                throw new \RuntimeException('この商品はすでに売り切れです');
            }

            if ((int) $item->user_id === $userId) {
                throw new \RuntimeException('自分の商品は予約できません');
            }

            $reservation = CheckoutReservation::where('item_id', $itemId)->first();

            if (
                $reservation !== null &&
                in_array($reservation->status, ['initializing', 'reserved'], true)
            ) {
                throw new \RuntimeException('この商品は現在予約中です');
            }

            if ($reservation !== null && $reservation->status === 'completed') {
                throw new \RuntimeException('この商品はすでに購入されています');
            }

            if ($reservation !== null && $reservation->status === 'expired') {
                $reservation->update([
                    'user_id' => $userId,
                    'status' => 'initializing',
                    'stripe_session_id' => null,
                    'expires_at' => null,
                ]);

                return $reservation->refresh();
            }

            if ($reservation === null) {
                return CheckoutReservation::create([
                    'item_id' => $itemId,
                    'user_id' => $userId,
                    'status' => 'initializing',
                ]);
            }

            throw new \RuntimeException('この予約は現在再利用できません');
        });
    }

    public function markAsReserved(int $reservationId, Session $session): void
    {
        DB::transaction(function () use ($reservationId, $session) {
            $reservation = CheckoutReservation::whereKey($reservationId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($reservation->status !== 'initializing') {
                throw new \RuntimeException('予約の状態が正しくありません');
            }

            if ($session->status !== 'open' || empty($session->id) || empty($session->expires_at)) {
                throw new \RuntimeException('Stripeセッションの情報が正しくありません');
            }

            $reservation->update([
                'stripe_session_id' => $session->id,
                'expires_at' => Carbon::createFromTimestamp($session->expires_at),
                'status' => 'reserved',
            ]);
        });
    }
}
