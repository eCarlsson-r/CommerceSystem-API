<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            BranchSeeder::class,
            CategorySeeder::class,
            SupplierSeeder::class,
            ProductSeeder::class,
            UserSeeder::class, // Includes Employees
            CustomerSeeder::class // Added the one we just discussed!
        ]);

        // Now that masters exist, we can simulate business activity
        $this->call([
            InitialStockSeeder::class,
            TransactionSeeder::class, // Fake Sales and POs
        ]);
    }
}
