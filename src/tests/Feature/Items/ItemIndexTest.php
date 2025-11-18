<?php

namespace Tests\Feature\Items;

use App\Models\User;
use App\Models\Item;
use App\Models\Like;
use App\Models\Purchase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_全商品を取得できる()
    {
        $items = Item::factory()->count(3) ->create();

        $response = $this->get('/');

        $response->assertStatus(200);

        foreach ($items as $item) {
            $response->assertSee($item->name);
        }
    }

    public function test_購入済み商品にはSOLDが表示される()
    {
        $item = Item::factory()->create();
        Purchase::factory()->create(['item_id' => $item->id]);

        $response = $this->get('/');

        $response->assertSee('SOLD');
    }

    public function test_自分が出品した商品は表示されない()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $userItem = Item::factory()->create([
            'user_id' => $user->id,
            'name' => 'ユーザーの商品ABC',
        ]);

        $otherItem = Item::factory()->create([
            'name' => '他人の商品XYZ',
        ]);

        $response = $this->get('/');

        $response->assertDontSee('ユーザーの商品ABC', false);
        $response->assertSee('他人の商品XYZ', false);
    }

    public function test_マイリストではいいねした商品だけが表示される()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $liked = Item::factory()->create();
        Like::factory()->create([
            'user_id' => $user->id,
            'item_id' => $liked->id,
        ]);

        $other = Item::factory()->create();

        $response = $this->get('/?tab=mylist');

        $response->assertSee($liked->name);
        $response->assertDontSee($other->name);
    }

    public function test_マイリストでも購入済み商品にはSOLDが表示される()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $item = Item::factory()->create();
        Like::factory()->create([
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);
        Purchase::factory()->create(['item_id' => $item->id]);

        $response = $this->get('/?tab=mylist');

        $response->assertSee('SOLD');
    }

    public function test_マイリストは未ログインの場合何も表示されない()
    {
        $response = $this->get('/?tab=mylist');

        $response->assertDontSee('item-card');
        $response->assertStatus(200);
    }

    public function test_商品名で部分一致検索ができる()
    {
        Item::factory()->create(['name' => 'Apple Watch']);
        Item::factory()->create(['name' => 'Mac Book']);

        $response = $this->get('/?keyword=Apple');

        $response->assertSee('Apple Watch');
        $response->assertDontSee('Mac Book');
    }

    public function test_検索状態がマイリストでも保持される()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $item = Item::factory()->create(['name' => 'iPhone']);

        Like::factory()->create([
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        $response = $this->get('/?tab=mylist&keyword=Phone');

        $response->assertSee('Phone');
        $response->assertSee('iPhone');
    }
}
