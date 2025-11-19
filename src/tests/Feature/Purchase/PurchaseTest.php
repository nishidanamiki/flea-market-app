<?php

namespace Tests\Feature\Purchase;

use App\Models\User;
use App\Models\Item;
use App\Models\Address;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_ログインユーザーは購入するボタンを押下すると購入が完了する()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create(['is_sold' => false]);
        $address = Address::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $response = $this->post("/purchase/{$item->id}", [
            'payment' => 'card',
            'address_id' => $address->id,
        ]);

        $response->assertRedirect(route('items.index'));

        $this->assertDatabaseHas('purchases', [
            'user_id' => $user->id,
            'item_id' => $item->id,
            'payment_method' => 'card',
            'status' => 'purchased',
        ]);

        $this->assertTrue($item->fresh()->is_sold);
    }

    public function test_購入済みの商品は購入できない()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create(['is_sold' => true]);
        $address = Address::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $response = $this->post("/purchase/{$item->id}", [
            'payment' => 'card',
            'address_id' => $address->id,
        ]);

        $response->assertRedirect(route('items.index'));
        $response->assertSessionHas('error', 'この商品は売り切れました');

        $this->assertDatabaseMissing('purchases', [
            'item_id' => $item->id,
        ]);
    }

    public function test_購入した商品は商品一覧画面でSOLDと表示される()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create(['is_sold' => false]);
        $address = Address::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $this->post("/purchase/{$item->id}", [
            'payment' => 'card',
            'address_id' => $address->id,
        ]);

        $response = $this->get('/');

        $response->assertSee('SOLD');
    }

    public function test_購入した商品はプロフィールの購入商品一覧に表示される()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $this->post("/purchase/{$item->id}", [
            'payment' => 'card',
            'address_id' => $address->id,
        ]);

        $response = $this->get('/mypage?page=buy');

        $response->assertSee($item->name);
    }

    public function test_購入した商品に送付先住所が紐づいて登録される()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create(['is_sold' => false]);
        $address = Address::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $response = $this->post("/purchase/{$item->id}", [
            'payment' => 'card',
            'address_id' => $address->id,
        ]);

        $response->assertRedirect(route('items.index'));

        $this->assertDatabaseHas('purchases', [
            'user_id' => $user->id,
            'item_id' => $item->id,
            'address_id' => $address->id,
        ]);
    }
}
