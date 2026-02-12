<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            [
                'name' => 'Regular Customer 1',
                'mobile' => '082100000001',
                'email' => 'customer1@example.com',
                'address' => 'Medan',
                'balance' => 0,
                'points' => 150,
            ],
            [
                'name' => 'Loyal Member',
                'mobile' => '082100000002',
                'email' => 'customer2@example.com',
                'address' => 'Binjai',
                'balance' => 0,
                'points' => 2500,
            ],
        ];

        foreach ($customers as $customer) {
            Customer::create($customer);
        }
    }
}
