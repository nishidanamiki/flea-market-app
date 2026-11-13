<?php

namespace App\Services;

use App\Models\Item;
use App\Models\CheckoutReservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Stripe\Checkout\Session;
use Carbon\Carbon;

class CheckoutReservationService
{
    public function reserve(int $itemId, int $userId, ?int $addressId = null, ?string $paymentMethod = null): CheckoutReservation
    {
        return DB::transaction(function () use ($itemId, $userId, $addressId, $paymentMethod) {
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

            if ($reservation !== null && $reservation->status === 'initializing') {
                $canRetry =
                    (int) $reservation->user_id === $userId &&
                    $addressId !== null &&
                    (int) $reservation->address_id === $addressId &&
                    in_array($paymentMethod, ['card', 'konbini'], true) &&
                    $reservation->payment_method === $paymentMethod &&
                    $reservation->item_name === $item->name &&
                    (int) $reservation->amount === (int) $item->price &&
                    !empty($reservation->attempt_key) &&
                    $reservation->updated_at !== null &&
                    $reservation->updated_at->gt(now()->subHours(23));

                if ($canRetry) {
                    return $reservation;
                }

                throw new \RuntimeException('この商品は現在予約中です');
            }

            if ($reservation !== null && $reservation->status === 'reserved') {
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
                    'attempt_key' => (string) Str::uuid(),
                    'address_id' => $addressId,
                    'payment_method' => $paymentMethod,
                    'item_name' => $item->name,
                    'amount' => $item->price,
                ]);

                return $reservation->refresh();
            }

            if ($reservation === null) {
                return CheckoutReservation::create([
                    'item_id' => $itemId,
                    'user_id' => $userId,
                    'status' => 'initializing',
                    'attempt_key' => (string) Str::uuid(),
                    'address_id' => $addressId,
                    'payment_method' => $paymentMethod,
                    'item_name' => $item->name,
                    'amount' => $item->price,
                ]);
            }

            throw new \RuntimeException('この予約は現在再利用できません');
        });
    }

    public function findActiveSessionId(int $itemId, int $userId, int $addressId, string $paymentMethod): ?string
    {
        $reservation = CheckoutReservation::where('item_id', $itemId)->first();

        if (
            $reservation === null ||
            $reservation->status !== 'reserved' ||
            (int) $reservation->user_id !== $userId ||
            (int) $reservation->address_id !== $addressId ||
            $reservation->payment_method !== $paymentMethod ||
            empty($reservation->stripe_session_id) ||
            $reservation->expires_at === null ||
            $reservation->expires_at->isPast()
        ) {
            return null;
        }

        return $reservation->stripe_session_id;
    }

    public function markAsReserved(int $reservationId, Session $session): void
    {
        DB::transaction(function () use ($reservationId, $session) {
            $reservation = CheckoutReservation::whereKey($reservationId)
                ->lockForUpdate()
                ->firstOrFail();

            if (empty($session->id)) {
                throw new \RuntimeException('Stripeセッションの情報が正しくありません');
            }

            if (
                $reservation->status === 'reserved' &&
                $reservation->stripe_session_id === $session->id
            ) {
                return;
            }

            if ($reservation->status !== 'initializing') {
                throw new \RuntimeException('予約の状態が正しくありません');
            }

            if ($session->status !== 'open' || empty($session->expires_at)) {
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
