<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Branch;
use App\Models\Stock;

class StockSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::all();
        $branches = Branch::all();

        foreach ($branches as $branch) {
            foreach ($products as $product) {
                Stock::create([
                    'branch_id' => (string) $branch->id,
                    'product_id' => (string) $product->id,
                    'quantity' => rand(20, 100),
                    'purchase_price' => rand(5000, 15000),
                    'sale_price' => rand(20000, 35000),
                    'min_stock_level' => 10,
                ]);
            }
        }
    }
}
