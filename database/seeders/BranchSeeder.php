<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = [
            ['name' => 'Zard Medan Main', 'address' => 'Jl. Dr. Mansyur No. 123, Medan', 'phone' => '061-123456'],
            ['name' => 'Zard Binjai Store', 'address' => 'Jl. Sudirman No. 456, Binjai', 'phone' => '061-654321'],
            ['name' => 'Zard Jakarta Branch', 'address' => 'Jl. Thamrin No. 789, Jakarta', 'phone' => '021-987654'],
            ['name' => 'Zard Surabaya Outlet', 'address' => 'Jl. Tunjungan No. 101, Surabaya', 'phone' => '031-456789'],
            ['name' => 'Zard Bandung Store', 'address' => 'Jl. Braga No. 202, Bandung', 'phone' => '022-321654'],
        ];

        foreach ($branches as $branch) {
            Branch::updateOrCreate(
                ['name' => $branch['name']],
                $branch
            );
        }
    }
}
