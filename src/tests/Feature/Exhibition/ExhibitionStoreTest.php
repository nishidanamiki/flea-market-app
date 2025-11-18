<?php

namespace Tests\Feature\Exhibition;

use App\Models\User;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExhibitionStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_商品出品画面で入力した情報が正しく保存される()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $this->actingAs($user);

        $categories = Category::factory()->count(2)->create();

        $image = UploadedFile::fake()->create('test.png', 100, 'image/png');

        $postData = [
            'name' => 'テスト商品',
            'description' => 'テスト説明文です。',
            'condition' => '良好',
            'price' => 5000,
            'img_url' => $image,
            'categories' => $categories->pluck('id')->toArray(),
        ];

        $response = $this->post('/items',$postData);

        $response->assertRedirect(route('items.index'));

        $this->assertDatabaseHas('items', [
            'name' => 'テスト商品',
            'description' => 'テスト説明文です。',
            'condition' => '良好',
            'price' => 5000,
            'user_id' => $user->id,
        ]);

        Storage::disk('public')->assertExists('item_images/' . $image->hashName());

        foreach ($categories as $category) {
            $this->assertDatabaseHas('category_item', [
                'category_id' => $category->id,
            ]);
        }
    }
}
