<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;
use App\Services\LaravelAiKitService;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $categories = Category::all();

        $products = [
            [
                'sku' => 'WLP-001',
                'name' => 'Royal Damask Gold',
                'category_id' => $categories->where('name', 'Wallpaper')->first()?->id ?? 1,
                'description' => 'Premium gold damask pattern wallpaper. Perfect for adding a touch of luxury to your living room.',
                'base_price' => 150000
            ],
            [
                'sku' => 'WLP-002',
                'name' => 'Modern Geometric Grey',
                'category_id' => $categories->where('name', 'Wallpaper')->first()?->id ?? 1,
                'description' => 'Minimalist grey geometric pattern. Fits perfectly in a modern office or bedroom.',
                'base_price' => 125000
            ],
            [
                'sku' => 'WLP-003',
                'name' => 'Tropical Leaf Green',
                'category_id' => $categories->where('name', 'Wallpaper')->first()?->id ?? 1,
                'description' => 'Vibrant tropical leaf pattern for a fresh look. Ideal for bathrooms or feature walls.',
                'base_price' => 135000
            ],
            [
                'sku' => 'WLP-004',
                'name' => 'Vintage Floral Pastel',
                'category_id' => $categories->where('name', 'Vintage Wallpaper')->first()?->id ?? 5,
                'description' => 'Soft pastel floral vintage design that brings warmth and nostalgia to any space.',
                'base_price' => 160000
            ],
            [
                'sku' => 'WLP-005',
                'name' => 'Industrial Concrete Texture',
                'category_id' => $categories->where('name', 'Textured Wallpaper')->first()?->id ?? 4,
                'description' => 'Realistic faux concrete texture. Great for urban lofts and contemporary apartments.',
                'base_price' => 140000
            ],
            [
                'sku' => 'WLP-006',
                'name' => 'Ocean Waves Blue',
                'category_id' => $categories->where('name', 'Wallpaper')->first()?->id ?? 1,
                'description' => 'Calming blue ocean wave patterns. Creates a relaxing atmosphere in bedrooms.',
                'base_price' => 120000
            ],
            [
                'sku' => 'WLP-007',
                'name' => 'Kids Cartoon Forest',
                'category_id' => $categories->where('name', 'Murals')->first()?->id ?? 2,
                'description' => 'Cute cartoon forest mural. Brings imagination to life in a nursery or kids room.',
                'base_price' => 180000
            ],
            [
                'sku' => 'WLP-008',
                'name' => 'Faux Red Brick',
                'category_id' => $categories->where('name', 'Textured Wallpaper')->first()?->id ?? 4,
                'description' => 'Classic red brick wall effect without the cost of masonry.',
                'base_price' => 110000
            ]
        ];

        foreach ($products as $pData) {
            $product = Product::create($pData);

            // Add sample media for wallpapers
            if (str_starts_with($product->sku, 'WLP')) {
                $patterns = [
                    'WLP-001' => 'https://images.pexels.com/photos/1037992/pexels-photo-1037992.jpeg',
                    'WLP-002' => 'https://images.pexels.com/photos/172289/pexels-photo-172289.jpeg',
                    'WLP-003' => 'https://images.pexels.com/photos/1029606/pexels-photo-1029606.jpeg',
                    'WLP-004' => 'https://images.pexels.com/photos/135018/pexels-photo-135018.jpeg',
                    'WLP-005' => 'https://images.pexels.com/photos/235985/pexels-photo-235985.jpeg',
                    'WLP-006' => 'https://images.pexels.com/photos/129731/pexels-photo-129731.jpeg',
                    'WLP-007' => 'https://images.pexels.com/photos/210243/pexels-photo-210243.jpeg',
                    'WLP-008' => 'https://images.pexels.com/photos/207142/pexels-photo-207142.jpeg'
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

        $aiService = new LaravelAiKitService();
        $result = $aiService->syncAllProductEmbeddings();
        $this->command->info("Embeddings synced: {$result['updated']} products updated");
    }
}
