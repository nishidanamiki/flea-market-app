<?php

namespace Tests\Feature\Purchase;

use App\Models\User;
use App\Models\Item;
use App\Models\Address;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    public function test_支払い方法選択画面で選択肢が表示される()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $response = $this->get("/purchase/{$item->id}");

        $response->assertStatus(200);
        $response->assertSee('convenience');
        $response->assertSee('credit');
    }

    public function test_支払い方法を選択すると小計画面に反映される()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $response = $this->post("/purchase/{$item->id}", [
            'payment' => 'credit',
            'address_id' => $address->id,
        ]);

        $response->assertRedirect(route('items.index'));

        $this->assertDatabaseHas('purchases', [
            'user_id' => $user->id,
            'item_id' => $item->id,
            'payment_method' => 'credit',
        ]);
    }
}
