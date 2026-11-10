<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use App\Models\CheckoutReservation;
use App\Services\CheckoutReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Stripe\Checkout\Session;
use Tests\TestCase;

class CheckoutReservationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_商品が存在しない場合は予約できない(): void
    {
        $service = new CheckoutReservationService();

        $this->expectException(ModelNotFoundException::class);

        $service->reserve(999999, 1);
    }

    public function test_販売中の商品を予約できる(): void
    {
        $item = Item::factory()->create([
            'is_sold' => false,
        ]);

        $buyer = User::factory()->create();

        $service = new CheckoutReservationService();

        $reservation = $service->reserve($item->id, $buyer->id);

        $this->assertDatabaseHas('checkout_reservations', [
            'item_id' => $item->id,
            'user_id' => $buyer->id,
            'status' => 'initializing',
        ]);

        $this->assertEquals($item->id, $reservation->item_id);
    }

    public function test_売却済みの商品は予約できない(): void
    {
        $item = Item::factory()->create([
            'is_sold' => true,
        ]);

        $buyer = User::factory()->create();

        $service = new CheckoutReservationService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('この商品はすでに売り切れです');

        $service->reserve($item->id, $buyer->id);
    }

    public function test_自分の商品は予約できない(): void
    {
        $seller = User::factory()->create();

        $item = Item::factory()->create([
            'user_id' => $seller->id,
            'is_sold' => false,
        ]);

        $service = new CheckoutReservationService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('自分の商品は予約できません');

        $service->reserve($item->id, $seller->id);
    }

    public function test_予約中の商品は別の購入者が予約できない(): void
    {
        $seller = User::factory()->create();
        $buyerA = User::factory()->create();
        $buyerB = User::factory()->create();

        $item = Item::factory()->create([
            'user_id' => $seller->id,
            'is_sold' => false,
        ]);

        $service = new CheckoutReservationService();

        $service->reserve($item->id, $buyerA->id);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('この商品は現在予約中です');

        $service->reserve($item->id, $buyerB->id);
    }

    public function test_予約確定中の商品は別の購入者が予約できない(): void
    {
        $seller = User::factory()->create();
        $buyerA = User::factory()->create();
        $buyerB = User::factory()->create();

        $item = Item::factory()->create([
            'user_id' => $seller->id,
            'is_sold' => false,
        ]);

        CheckoutReservation::create([
            'item_id' => $item->id,
            'user_id' => $buyerA->id,
            'status' => 'reserved',
        ]);

        $service = new CheckoutReservationService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('この商品は現在予約中です');

        $service->reserve($item->id, $buyerB->id);
    }

    public function test_購入完了済みの予約は再利用できない(): void
    {
        $seller = User::factory()->create();
        $buyerA = User::factory()->create();
        $buyerB = User::factory()->create();

        $item = Item::factory()->create([
            'user_id' => $seller->id,
            'is_sold' => false,
        ]);

        CheckoutReservation::create([
            'item_id' => $item->id,
            'user_id' => $buyerA->id,
            'status' => 'completed',
        ]);

        $service = new CheckoutReservationService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('この商品はすでに購入されています');

        $service->reserve($item->id, $buyerB->id);
    }

    public function test_期限切れの予約は再利用できる(): void
    {
        $seller = User::factory()->create();
        $buyerA = User::factory()->create();
        $buyerB = User::factory()->create();

        $item = Item::factory()->create([
            'user_id' => $seller->id,
            'is_sold' => false,
        ]);

        $reservation = CheckoutReservation::create([
            'item_id' => $item->id,
            'user_id' => $buyerA->id,
            'status' => 'expired',
            'expires_at' => now()->subMinutes(30),
        ]);

        $service = new CheckoutReservationService();

        $newReservation = $service->reserve($item->id, $buyerB->id);

        $this->assertEquals($reservation->id, $newReservation->id);

        $this->assertDatabaseHas('checkout_reservations', [
            'id' => $reservation->id,
            'user_id' => $buyerB->id,
            'status' => 'initializing',
            'stripe_session_id' => null,
            'expires_at' => null,
        ]);

        $this->assertDatabaseCount('checkout_reservations', 1);
    }

    public function test_Stripeセッション登録後に予約状態が更新される(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();

        $item = Item::factory()->create([
            'user_id' => $seller->id,
            'is_sold' => false,
        ]);

        $service = new CheckoutReservationService();

        $reservation = $service->reserve($item->id, $buyer->id);

        $session = Session::constructFrom([
            'id' => 'cs_test_example123',
            'status' => 'open',
            'expires_at' => now()->addMinutes(30)->timestamp,
        ]);

        $service->markAsReserved($reservation->id, $session);

        $this->assertDatabaseHas('checkout_reservations', [
            'id' => $reservation->id,
            'stripe_session_id' => 'cs_test_example123',
            'status' => 'reserved',
        ]);

        $this->assertNotNull($reservation->fresh()->expires_at);
    }

    public function test_期限切れのStripeセッションでは予約を更新できない(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();

        $item = Item::factory()->create([
            'user_id' => $seller->id,
            'is_sold' => false,
        ]);

        $service = new CheckoutReservationService();

        $reservation = $service->reserve($item->id, $buyer->id);

        $session = Session::constructFrom([
            'id' => 'cs_test_expired123',
            'status' => 'expired',
            'expires_at' => now()->subMinutes(30)->timestamp,
        ]);

        try {
            $service->markAsReserved($reservation->id, $session);

            $this->fail('例外が発生するはずです');
        } catch (\RuntimeException $e) {
            $this->assertSame(
                'Stripeセッションの情報が正しくありません',
                $e->getMessage()
            );
        }

        $this->assertDatabaseHas('checkout_reservations', [
            'id' => $reservation->id,
            'status' => 'initializing',
            'stripe_session_id' => null,
            'expires_at' => null,
        ]);
    }

    public function test_予約済みの予約は二十更新できない(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();

        $item = Item::factory()->create([
            'user_id' => $seller->id,
            'is_sold' => false,
        ]);

        $reservation = CheckoutReservation::create([
            'item_id' => $item->id,
            'user_id' => $buyer->id,
            'status' => 'reserved',
            'stripe_session_id' => 'cs_test_original123',
            'expires_at' => now()->addMinutes(30),
        ]);

        $session = Session::constructFrom([
            'id' => 'cs_test_another123',
            'status' => 'open',
            'expires_at' => now()->addMinutes(30)->timestamp,
        ]);

        $service = new CheckoutReservationService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('予約の状態が正しくありません');

        $service->markAsReserved($reservation->id, $session);
    }
}
