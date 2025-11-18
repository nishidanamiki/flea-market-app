<?php

namespace Tests\Feature\Items;

use App\Models\User;
use App\Models\Item;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Like;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_商品詳細ページで必要な情報が表示される()
    {
        $item = Item::factory()->create([
            'name' => 'テスト商品',
            'brand' => 'テストブランド',
            'price' => 5000,
            'description' => '説明テキスト',
            'condition' => '良好',
            'img_url' => 'test.jpg',
        ]);

        $cat1 = Category::factory()->create(['name' => 'カテゴリA']);
        $cat2 = Category::factory()->create(['name' => 'カテゴリB']);
        $item->categories()->attach([$cat1->id, $cat2->id]);

        Like::factory()->count(3)->create([
            'item_id' => $item->id,
            'user_id' => User::factory(),
        ]);

        Comment::factory()->count(2)->create([
            'item_id' => $item->id,
            'comment' => 'コメント内容',
        ]);

        $response = $this->get("/item/{$item->id}");

        $response->assertSee('テスト商品');
        $response->assertSee('テストブランド');
        $response->assertSee('5,000');
        $response->assertSee('説明テキスト');
        $response->assertSee('良好');
        $response->assertSee('test.jpg');
        $response->assertSee('カテゴリA');
        $response->assertSee('カテゴリB');
        $response->assertSee('3'); //いいね数
        $response->assertSee('2'); //コメント数
    }

    public function test_コメントしたユーザー名とコメント内容が表示される()
    {
        $user = User::factory()->create(['name' => '山田太郎']);

        $item = Item::factory()->create();

        Comment::factory()->create([
            'user_id' => $user->id,
            'item_id' => $item->id,
            'comment' => 'とても良い商品です！',
        ]);

        $response = $this->get("/item/{$item->id}");

        $response->assertSee('山田太郎');
        $response->assertSee('とても良い商品です！');
    }

    public function test_存在しない商品IDを開くと404が表示される()
    {
        $response = $this->get('/item/999999');

        $response->assertStatus(404);
    }
}
