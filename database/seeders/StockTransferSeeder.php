<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Branch;
use App\Models\Product;
use App\Models\User;

class StockTransferSeeder extends Seeder
{
    public function run(): void
    {
        $branches = Branch::all();
        $products = Product::all();
        $users = User::where('role', 'admin')->orWhere('role', 'manager')->get();

        if ($branches->count() < 2 || $products->isEmpty() || $users->isEmpty()) return;

        for ($i = 0; $i < 5; $i++) {
            $fromBranch = $branches->random();
            $toBranch = $branches->where('id', '!=', $fromBranch->id)->random();

            $transfer = StockTransfer::create([
                'from_branch_id' => (string) $fromBranch->id,
                'to_branch_id' => (string) $toBranch->id,
                'date' => now()->subDays(rand(1, 15)),
                'status' => collect(['M', 'R'])->random(),
                'created_by' => $users->random()->id,
            ]);

            // Add 1-3 items
            $itemsCount = rand(1, 3);
            for ($j = 0; $j < $itemsCount; $j++) {
                $product = $products->random();
                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_id' => $product->id,
                    'quantity' => rand(5, 20),
                ]);
            }
        }
    }
}
