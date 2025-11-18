<?php

namespace Tests\Feature\User;

use App\Models\User;
use App\Models\Item;
use App\Models\Address;
use App\Models\Purchase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class profileTest extends TestCase
{
    use RefreshDatabase;

    public function test_プロフィールページでユーザー情報が正しく取得できる()
    {
        $user = User::factory()->create([
            'name' => 'テストユーザー',
            'profile_image_path' => null,
        ]);

        $myItem = Item::factory()->create([
            'user_id' => $user->id,
            'name' => '出品商品A',
        ]);

        $purchasedItem = Item::factory()->create(['name' => '購入商品B']);
        Purchase::factory()->create([
            'user_id' => $user->id,
            'item_id' => $purchasedItem->id,
        ]);

        $this->actingAs($user);

        $response = $this->get('/mypage?page=sell');
        $response->assertStatus(200);
        $response->assertSee('テストユーザー');
        $response->assertSee('No Image');
        $response->assertSee('出品商品A');

        $response = $this->get('/mypage?page=buy');
        $response->assertStatus(200);
        $response->assertSee('購入商品B');
    }

    public function test_プロフィール編集画面で初期値が正しく表示される()
    {
        $user = User::factory()->create([
            'name' => '初期ユーザー',
            'profile_image_path' => null,
        ]);

        $address = Address::factory()->create([
            'user_id' => $user->id,
            'postal_code' => '111-2222',
            'address' => '東京都テスト区１－１',
            'building' => 'テストビル１０１',
        ]);

        $this->actingAs($user);

        $response = $this->get('/mypage/profile');

        $response->assertStatus(200);

        $response->assertSee('placeholder');
        $response->assertSee('value="初期ユーザー"', false);
        $response->assertSee('value="111-2222"', false);
        $response->assertSee('value="東京都テスト区１－１', false);
        $response->assertSee('value="テストビル１０１', false);
    }
}
