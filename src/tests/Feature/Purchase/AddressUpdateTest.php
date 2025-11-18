<?php

namespace tests\Feature\Purchase;

use App\Models\User;
use App\Models\Item;
use App\Models\Address;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddressUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_送付先住所を変更すると購入画面に反映される()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create();

        $this->actingAs($user);

        $response = $this->post(route('address.update', $item->id), [
            'postal_code' => '123-4567',
            'address' => '東京都渋谷区テスト１－１',
            'building' => 'テストマンション１０１',
        ]);

        $address = Address::where('user_id', $user->id)->first();

        $response = $this->get(route('purchase', $item->id));

        $response->assertSee("〒{$address->postal_code}");
        $response->assertSee($address->address . $address->building);

        $response->assertSee('value="' . $address->id . '"', false);
    }
}
