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
            ['name' => 'Zard Medan Main', 'address' => 'Jl. Dr. Mansyur', 'phone' => '061-123456'],
            ['name' => 'Zard Binjai Store', 'address' => 'Jl. Sudirman', 'phone' => '061-654321'],
        ];

        foreach ($branches as $branch) {
            Branch::create($branch);
        }
    }
}
