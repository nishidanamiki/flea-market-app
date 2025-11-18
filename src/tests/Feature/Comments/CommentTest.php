<?php

namespace Tests\Feature\Comments;

use App\Models\User;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_ログイン済みユーザーはコメントを送信できる()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create();

        $this->actingAs($user);

        $response = $this->post("/items/{$item->id}/comment", [
            'comment' => 'とても良い商品ですね！'
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('comments', [
            'user_id' => $user->id,
            'item_id' => $item->id,
            'comment' => 'とても良い商品ですね！',
        ]);
    }

    public function test_未ログインユーザーはコメントを送信できない()
    {
        $item = Item::factory()->create();

        $response = $this->post("/items/{$item->id}/comment", [
            'comment' => 'ログインしてません',
        ]);

        $response->assertRedirect('/login');

        $this->assertDatabaseMissing('comments', [
            'comment' => 'ログインしてません'
        ]);
    }

    public function test_コメント未入力の場合はバリデーションメッセージが表示される()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create();

        $this->actingAs($user);

        $response = $this->post("/items/{$item->id}/comment", [
            'comment' => ''
        ]);

        $response->assertSessionHasErrors(['comment']);
    }

    public function test_コメントが255字以上の場合バリデーションメッセージが表示される()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create();

        $this->actingAs($user);

        $longComment = str_repeat('あ', 256);

        $response = $this->post("/items/{$item->id}/comment", [
            'comment' => $longComment,
        ]);

        $response->assertSessionHasErrors(['comment']);
    }
}
