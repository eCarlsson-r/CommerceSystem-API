<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\Product;
use Illuminate\Support\Str;

class PurchaseOrderSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = Supplier::all();
        $products = Product::all();

        if ($suppliers->isEmpty() || $products->isEmpty()) return;

        for ($i = 0; $i < 5; $i++) {
            $totalAmount = 0;
            $items = [];

            // Prepare 2-4 items first to calculate total
            $itemsCount = rand(2, 4);
            for ($j = 0; $j < $itemsCount; $j++) {
                $product = $products->random();
                $qty = rand(10, 50);
                $price = rand(5000, 15000);
                $total = $qty * $price;
                
                $items[] = [
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'total_price' => $total,
                ];
                $totalAmount += $total;
            }

            $po = PurchaseOrder::create([
                'order_number' => 'PO-' . strtoupper(Str::random(6)),
                'supplier_id' => $suppliers->random()->id,
                'order_date' => now()->subDays(rand(1, 10)),
                'expected_date' => now()->addDays(rand(5, 15)),
                'total_amount' => $totalAmount,
                'status' => collect(['pending', 'completed', 'cancelled'])->random(),
            ]);

            foreach ($items as $item) {
                $item['purchase_order_id'] = $po->id;
                PurchaseOrderItem::create($item);
            }
        }
    }
}
