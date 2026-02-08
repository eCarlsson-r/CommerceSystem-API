<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    // database/seeders/ProductSeeder.php
    public function run()
    {
        $coffee = Product::create([
            'code' => 'COF-001',
            'name' => 'Signature Roasted Beans',
            'category_id' => 1,
            'description' => 'Premium store-blend coffee beans.',
        ]);

        // Attach a fake media record
        $coffee->media()->create([
            'file_name' => 'coffee-beans',
            'path' => 'uploads/product/coffee-beans.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size' => 102400,
            'model_id' => $coffee->id,
            'model_type' => Product::class,
        ]);
    }
}
