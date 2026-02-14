<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cart;
use App\Models\User;
use App\Models\Product;
use App\Models\Customer;

class CartSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = Customer::all();
        $products = Product::all();

        if ($customers->isEmpty() || $products->isEmpty()) {
            return;
        }

        foreach ($customers as $customer) {
            if (Cart::where('customer_id', $customer->id)->exists()) {
                continue;
            }

            // Add 1-3 items to each customer's cart
            $randomProducts = $products->random(rand(1, 3));

            foreach ($randomProducts as $product) {
                Cart::create([
                    'customer_id' => $customer->id,
                    'product_id' => $product->id,
                    'quantity' => rand(1, 5)
                ]);
            }
        }
    }
}
