<?php

namespace Tests\Feature\Likes;

use App\Models\User;
use App\Models\Item;
use App\Models\Like;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LikeTest extends TestCase
{
    use RefreshDatabase;

    public Function test_ログインユーザーはいいねできる()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create();

        $this->actingAs($user);

        $response = $this->post(route('items.like', $item->id));

        $response->assertRedirect();

        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);
    }

    public function test_いいね後にいいね数が画面に増えて表示される()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create();

        $this->actingAs($user);

        $responseBefore = $this->get("/item/{item->id}");
        $responseBefore->assertSee('0');

        $this->post(route('items.like', $item->id));

        $responseAfter = $this->get("/item/{item->id}");
        $responseAfter->assertSee('1');
    }

    public function test_いいね済みの場合アイコンが塗りつぶし表示になる()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create();

        Like::factory()->create([
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        $this->actingAs($user);

        $response = $this->get("/item/{$item->id}");

        $response->assertSee('fa-solid fa-star');
    }

    public function test_いいね解除するとDBから削除される()
    {
        $user = User::factory()->create();
        $item = ITem::factory()->create();

        $like = Like::factory()->create([
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        $this->actingAs($user);

        $response = $this->delete(route('items.unlike', $item->id));

        $response->assertRedirect();

        $this->assertDatabaseMissing('likes',[
            'id' => $like->id,
        ]);
    }

    public function test_いいね解除後にいいね数が画面で減って表示される()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create();

        Like::factory()->create([
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        $this->actingAs($user);

        $responseBefore = $this->get("/item/{$item->id}");
        $responseBefore->assertSee('1');

        $this->delete(route('items.unlike', $item->id));

        $responseAfter = $this->get("/item/{$item->id}");
        $responseAfter->assertSee('0');
    }

    public function test_未ログインユーザーはいいねできずログインページへリダイレクトされる()
    {
        $item = Item::factory()->create();

        $response = $this->post(route('items.like', $item->id));

        $response->assertRedirect('/login');
    }
}
