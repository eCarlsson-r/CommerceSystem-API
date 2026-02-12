<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Support\Str;

class SaleSeeder extends Seeder
{
    public function run(): void
    {
        $branches = Branch::all();
        $customers = Customer::all();
        $products = Product::all();

        foreach ($branches as $branch) {
            $employee = Employee::where('branch_id', $branch->id)->first();
            
            if (!$employee) continue;

            // Create 10 sales per branch
            for ($i = 0; $i < 10; $i++) {
                $subtotal = 0;
                $customer = $customers->random();

                $sale = Sale::create([
                    'invoice_number' => 'INV-' . strtoupper(Str::random(8)),
                    'date' => now()->subDays(rand(0, 30)),
                    'branch_id' => $branch->id,
                    'employee_id' => $employee->id,
                    'customer_id' => $customer->id,
                    'status' => 'completed',
                    'subtotal' => 0, // Will update after items
                    'tax_amount' => 0,
                    'discount_amount' => 0,
                    'grand_total' => 0,
                ]);

                // Add 1-3 items per sale
                $itemsCount = rand(1, 3);
                for ($j = 0; $j < $itemsCount; $j++) {
                    $product = $products->random();
                    $qty = rand(1, 5);
                    $price = rand(20000, 35000);
                    $total = $qty * $price;

                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $product->id,
                        'quantity' => $qty,
                        'unit_price' => $price,
                        'total_price' => $total,
                    ]);

                    $subtotal += $total;
                }

                $grandTotal = $subtotal; // Simple case no tax/discount

                $sale->update([
                    'subtotal' => $subtotal,
                    'grand_total' => $grandTotal,
                ]);

                // Create payment
                SalePayment::create([
                    'sale_id' => $sale->id,
                    'payment_method' => collect(['cash', 'qris', 'card'])->random(),
                    'amount_paid' => $grandTotal,
                ]);
            }
        }
    }
}
