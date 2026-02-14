<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Branch;

use Illuminate\Support\Str;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = Customer::all();
        $products = Product::all();
        $branches = Branch::all();

        if ($customers->isEmpty() || $products->isEmpty()) {
            return;
        }
        
        // Clear previous data to prevent constraints issues
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        Order::truncate();
        OrderItem::truncate();
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        // Create some sample orders
        foreach ($customers as $index => $customer) {
            // Skip if customer already has orders to prevent duplicates on re-seed
            if (Order::where('customer_id', $customer->id)->exists()) {
                continue;
            }

            // Create 1-2 orders per customer
            for ($i = 0; $i < rand(1, 2); $i++) {
                $status = fake()->randomElement(['pending', 'processing', 'shipped', 'cancelled']);
                
                $order = Order::create([
                    'customer_id' => $customer->id,
                    'order_number' => 'WEB-' . date('Y') . '-' . strtoupper(Str::uuid()),
                    'status' => $status,
                    'total_amount' => 0, // Will calculate below
                    'shipping_address' => fake()->address(),
                    'courier_service' => fake()->randomElement(['JNE', 'J&T', 'SiCepat', 'GoSend']),
                    'tracking_number' => $status === 'shipped' ? 'RES' . rand(100000, 999999) : null,
                    'branch_id' => ($status === 'processing' || $status === 'shipped') ? $branches->random()->id : null
                ]);

                $total = 0;
                $orderItems = $products->random(rand(1, 4));

                foreach ($orderItems as $product) {
                    $qty = rand(1, 3);
                    $price = $product->stocks()->where('branch_id', $branches->random()->id)->first()->sale_price;
                    
                    try {
                        $order->items()->create([
                            'product_id' => $product->id,
                            'quantity' => $qty,
                            'unit_price' => $price,
                            'total_price' => $qty * $price
                        ]);
                    } catch (\Exception $e) {
                        print_r($e->getMessage());
                        // Creating duplicate items in same order might fail if unique constraint exists
                        continue;
                    }

                    $total += ($qty * $price);
                }

                $order->update(['total_amount' => $total]);
            }
        }
    }
}
