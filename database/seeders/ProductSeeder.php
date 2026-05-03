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
            ],
            [
                'sku' => 'WLP-001',
                'name' => 'Royal Damask Gold',
                'category_id' => $categories->where('name', 'Wallpaper')->first()?->id ?? 11,
                'description' => 'Premium gold damask pattern wallpaper.',
                'base_price' => 150000
            ],
            [
                'sku' => 'WLP-002',
                'name' => 'Modern Geometric Grey',
                'category_id' => $categories->where('name', 'Wallpaper')->first()?->id ?? 11,
                'description' => 'Minimalist grey geometric pattern.',
                'base_price' => 125000
            ],
            [
                'sku' => 'WLP-003',
                'name' => 'Tropical Leaf Green',
                'category_id' => $categories->where('name', 'Wallpaper')->first()?->id ?? 11,
                'description' => 'Vibrant tropical leaf pattern for a fresh look.',
                'base_price' => 135000
            ]
        ];

        foreach ($products as $pData) {
            $product = Product::create($pData);

            // Add sample media for wallpapers
            if (str_starts_with($product->sku, 'WLP')) {
                $patterns = [
                    'WLP-001' => 'https://images.pexels.com/photos/1037992/pexels-photo-1037992.jpeg',
                    'WLP-002' => 'https://images.pexels.com/photos/172289/pexels-photo-172289.jpeg',
                    'WLP-003' => 'https://images.pexels.com/photos/1029606/pexels-photo-1029606.jpeg'
                ];

                $product->media()->create([
                    'file_name' => $product->sku . '.jpg',
                    'mime_type' => 'image/jpeg',
                    'extension' => 'jpg',
                    'size' => 1024,
                    'disk' => 'public',
                    'path' => $patterns[$product->sku] ?? $patterns['WLP-001']
                ]);
            }
        }
    }
}
