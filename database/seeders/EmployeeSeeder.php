<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Employee;
use App\Models\Branch;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $branches = Branch::all();
        $managerUser = User::where('username', 'manager')->first();
        $staffUser = User::where('username', 'staff')->first();

        if ($managerUser && $branches->count() > 0) {
            Employee::create([
                'user_id' => $managerUser->id,
                'branch_id' => $branches->first()->id,
                'name' => 'Manager Zard',
                'gender' => 'M',
                'status' => 'active',
                'join_date' => now()->subYear(),
                'phone' => '08123456789',
                'email' => 'manager@zard.com'
            ]);
        }

        if ($staffUser && $branches->count() > 1) {
            Employee::create([
                'user_id' => $staffUser->id,
                'branch_id' => $branches->get(1)->id,
                'name' => 'Staff Zard',
                'gender' => 'F',
                'status' => 'active',
                'join_date' => now()->subMonths(6),
                'phone' => '08123456780',
                'email' => 'staff@zard.com'
            ]);
        }
    }
}
