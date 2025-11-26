<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('items')->insert([
            [
                'user_id' => 1,
                'name' => '腕時計',
                'price' => 15000,
                'brand' => 'Rolax',
                'description' => 'スタイリッシュなデザインのメンズ腕時計',
                'img_url' => 'images/seeder/Armani+Mens+Clock.jpg',
                'condition' => '良好',
            ],
            [
                'user_id'=> 1,
                'name' => 'HDD',
                'price' => 5000,
                'brand' => '西芝',
                'description' => '高速で信頼性の高いハードディスク',
                'img_url' => 'images/seeder/HDD+Hard+Disk.jpg',
                'condition' => '目立った傷や汚れなし',
            ],
            [
                'user_id' => 1,
                'name' => '玉ねぎ3束',
                'price' => 300,
                'brand' => '',
                'description' => '新鮮な玉ねぎ3束のセット',
                'img_url' => 'images/seeder/iLoveIMG+d.jpg',
                'condition' => 'やや傷や汚れあり',
            ],
            [
                'user_id' => 1,
                'name' => '革靴',
                'price' => 4000,
                'brand' => '',
                'description' => 'クラシックなデザインの革靴',
                'img_url' => 'images/seeder/Leather+Shoes+Product+Photo.jpg',
                'condition' => '状態が悪い',
            ],
            [
                'user_id' => 1,
                'name' => 'ノートPC',
                'price' => 45000,
                'brand' => '',
                'description' => '高性能なノートパソコン',
                'img_url' => 'images/seeder/Living+Room+Laptop.jpg',
                'condition' => '良好',
            ],
            [
                'user_id' => 2,
                'name' => 'マイク',
                'price' => 8000,
                'brand' => '',
                'description' => '高品質のレコーディング用マイク',
                'img_url' => 'images/seeder/Music+Mic+4632231.jpg',
                'condition' => '目立った傷や汚れなし',
            ],
            [
                'user_id' => 2,
                'name' => 'ショルダーバッグ',
                'price' => 3500,
                'brand' => '',
                'description' => 'おしゃれなショルダーバッグ',
                'img_url' => 'images/seeder/Purse+fashion+pocket.jpg',
                'condition' => 'やや傷や汚れあり',
            ],
            [
                'user_id' => 2,
                'name' => 'タンブラー',
                'price' => 500,
                'brand' => '',
                'description' => '使いやすいタンブラー',
                'img_url' => 'images/seeder/Tumbler+souvenir.jpg',
                'condition' => '状態が悪い',
            ],
            [
                'user_id' => 2,
                'name' => 'コーヒーミル',
                'price' => 4000,
                'brand' => 'Starbacks',
                'description' => '手動のコーヒーミル',
                'img_url' => 'images/seeder/Waitress+with+Coffee+Grinder.jpg',
                'condition' => '良好',
            ],
            [
                'user_id' => 2,
                'name' => 'メイクセット',
                'price' => 2500,
                'brand' => '',
                'description' => '便利なメイクアップセット',
                'img_url' => 'images/seeder/外出メイクアップセット.jpg',
                'condition' => '目立った傷や汚れなし',
            ],
        ]);

        DB::table('category_item')->insert([
            ['category_id' => 1, 'item_id' => 1],
            ['category_id' => 5, 'item_id' => 1],
            ['category_id' => 2, 'item_id' => 2],
            ['category_id' => 10, 'item_id' => 3],
            ['category_id' => 1, 'item_id' => 4],
            ['category_id' => 5, 'item_id' => 4],
            ['category_id' => 2, 'item_id' => 5],
            ['category_id' => 2, 'item_id' => 6],
            ['category_id' => 1, 'item_id' => 7],
            ['category_id' => 4, 'item_id' => 7],
            ['category_id' => 10, 'item_id' => 8],
            ['category_id' => 10, 'item_id' => 9],
            ['category_id' => 6, 'item_id' => 10],
        ]);
    }
}
