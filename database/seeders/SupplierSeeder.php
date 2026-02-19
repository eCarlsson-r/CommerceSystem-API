<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'Global Tech Distro',
                'contact_person' => 'Budi Setiawan',
                'tax_id' => '1234567890',
                'address' => 'Jl. Setiabudi No. 123, Jakarta',
                'email' => 'budi@globaltech.com',
                'phone' => '0812-3456-7890'
            ],
            [
                'name' => 'Crystal Imports Ltd',
                'contact_person' => 'Sari Dewi',
                'tax_id' => '0987654321',
                'address' => 'Jl. Crystal No. 456, Bandung',
                'email' => 'sari@crystalimports.com',
                'phone' => '0813-9876-5432'
            ],
            [
                'name' => 'Jewelry Wholesale Co',
                'contact_person' => 'Ahmad Rahman',
                'tax_id' => '1122334455',
                'address' => 'Jl. Emas No. 789, Surabaya',
                'email' => 'ahmad@jewelrywholesale.com',
                'phone' => '0814-5678-9012'
            ],
            [
                'name' => 'Toy Manufacturers Inc',
                'contact_person' => 'Maya Sari',
                'tax_id' => '5566778899',
                'address' => 'Jl. Mainan No. 101, Semarang',
                'email' => 'maya@toymanufacturers.com',
                'phone' => '0815-3456-7890'
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::updateOrCreate(
                ['name' => $supplier['name']],
                $supplier
            );
        }
    }
}
