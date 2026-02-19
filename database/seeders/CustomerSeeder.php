<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        $customers = [];

        // Add more dummy customers
        for ($i = 0; $i < 20; $i++) {
            $customers[] = [
                'name' => $faker->name(),
                'mobile' => $faker->phoneNumber(),
                'email' => $faker->unique()->safeEmail(),
                'address' => $faker->address(),
                'balance' => $faker->randomFloat(2, 0, 100000),
                'points' => $faker->numberBetween(0, 5000),
            ];
        }

        foreach ($customers as $customer) {
            // Ensure we have a username for the user record. Prefer email, fallback to mobile or generated slug.
            $username = $customer['email'] ?? ($customer['mobile'] ?? null);
            if (!$username) {
                $username = 'customer_' . Str::slug($customer['name']) . '_' . Str::random(4);
            }

            // Create or update the associated User
            $user = User::updateOrCreate(
                ['username' => $username],
                [
                    'password' => Hash::make('password'),
                    'role' => 'customer'
                ]
            );

            // Attach user_id to customer and create/update customer
            $customer['user_id'] = $user->id;

            Customer::updateOrCreate(
                ['email' => $customer['email'] ?? null, 'user_id' => $user->id],
                $customer
            );
        }
    }
}
