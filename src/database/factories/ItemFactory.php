<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->unique()->word(),
            'price' => $this->faker->numberBetween(100, 10000),
            'brand' => $this->faker->word(),
            'description' => $this->faker->sentence(),
            'condition' => '新品',
            'img_url' => $this->faker->unique()->lexify('image_????.jpg'),
            'is_sold' => false,
        ];
    }
}
