<?php

namespace Database\Factories;

use App\Models\Purchase;
use App\Models\User;
use App\Models\Item;
use App\Models\Address;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseFactory extends Factory
{
    protected $model = Purchase::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'item_id' => function () {
                return Item::factory()->create(['is_sold' => true])->id;
            },
            'payment_method' => 'card',
            'status' =>'purchased',
            'address_id' => Address::factory(),
        ];
    }
}
