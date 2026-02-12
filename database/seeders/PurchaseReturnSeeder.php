<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\PurchaseOrder;
use Illuminate\Support\Str;

class PurchaseReturnSeeder extends Seeder
{
    public function run(): void
    {
        $pos = PurchaseOrder::with('items')->where('status', 'completed')->get();

        if ($pos->isEmpty()) return;

        foreach ($pos->take(3) as $po) {
            $return = PurchaseReturn::create([
                'return_number' => 'RET-' . strtoupper(Str::random(6)),
                'purchase_order_id' => $po->id,
                'return_date' => now()->subDays(rand(1, 5)),
                'total_amount' => 0, // Will update
                'status' => 'completed',
            ]);

            $total = 0;
            foreach ($po->items->take(1) as $item) {
                $qty = rand(1, 5);
                $subtotal = $qty * $item->unit_price;

                PurchaseReturnItem::create([
                    'purchase_return_id' => $return->id,
                    'product_id' => $item->product_id,
                    'quantity' => $qty,
                    'unit_price' => $item->unit_price,
                    'total_price' => $subtotal,
                ]);

                $total += $subtotal;
            }

            $return->update(['total_amount' => $total]);
        }
    }
}
