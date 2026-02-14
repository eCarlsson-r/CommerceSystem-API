<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Settings;
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
            UserSeeder::class,
            EmployeeSeeder::class,
            CustomerSeeder::class,
            ProductSeeder::class,
            StockSeeder::class,
            SaleSeeder::class,
            PurchaseOrderSeeder::class,
            StockTransferSeeder::class,
            PurchaseReturnSeeder::class,
            StockLogSeeder::class,
            CartSeeder::class,
            OrderSeeder::class,
        ]);

        Settings::updateOrCreate(
            ['key' => 'cost_cipher_key'],
            ['value' => 'MEDANCLUBS']
        );
    }
}
