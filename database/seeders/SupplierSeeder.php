<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    // database/seeders/SupplierSeeder.php
    public function run(): void
    {
        Supplier::create([
            'name' => 'Global Tech Distro',
            'contact_person' => 'Budi Setiawan',
            'email' => 'budi@globaltech.com',
            'phone' => '0812-3456-7890'
        ]);
    }   
}
