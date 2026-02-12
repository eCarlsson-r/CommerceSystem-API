<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $categories = Category::all();

        $products = [
            [
                'sku' => '0010000001',
                'name' => 'Dolphin Crystal',
                'category_id' => $categories->where('name', 'Crystals')->first()?->id ?? 1,
                'description' => 'Single dolphin crystal.',
            ],
            [
                'sku' => '001000002',
                'name' => 'Lotus Crystal',
                'category_id' => $categories->where('name', 'Crystals')->first()?->id ?? 1,
                'description' => 'Single lotus crystal.',
            ],
            [
                'sku' => '002000001',
                'name' => 'Rose Earring',
                'category_id' => $categories->where('name', 'Jewelry')->first()?->id ?? 2,
                'description' => 'A pair of rose earring.',
            ],
            [
                'sku' => '002000002',
                'name' => 'Gold Chain',
                'category_id' => $categories->where('name', 'Jewelry')->first()?->id ?? 2,
                'description' => 'A pair of gold chain.',
            ],
            [
                'sku' => '0030000001',
                'name' => 'Winnie The Pooh',
                'category_id' => $categories->where('name', 'Dolls')->first()?->id ?? 3,
                'description' => 'Doll of Winnie The Pooh holding honey cup.',
            ],
            [
                'sku' => '0030000002',
                'name' => 'Keroppi',
                'category_id' => $categories->where('name', 'Dolls')->first()?->id ?? 3,
                'description' => 'Doll of Keroppi.',
            ]
        ];

        foreach ($products as $pData) {
            Product::create($pData);
        }
    }
}
