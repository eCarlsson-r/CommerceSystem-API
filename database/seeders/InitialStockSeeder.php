<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Branch;
use App\Models\Stock;
use App\Models\StockLog;

class InitialStockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $products = Product::all();
        $branches = Branch::all();

        foreach ($branches as $branch) {
            foreach ($products as $product) {
                $qty = rand(50, 100);
                
                // 1. Set the stock level
                Stock::create([
                    'branch_id' => $branch->id,
                    'product_id' => $product->id,
                    'quantity' => $qty
                ]);

                // 2. Log the initial entry (Kartu Stok)
                StockLog::create([
                    'branch_id' => $branch->id,
                    'product_id' => $product->id,
                    'type' => 'adjustment',
                    'quantity_change' => $qty,
                    'reference_number' => 'INIT-STOCK',
                    'description' => 'Initial inventory seeding'
                ]);
            }
        }
    }
}
