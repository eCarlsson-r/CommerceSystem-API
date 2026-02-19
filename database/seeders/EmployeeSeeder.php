<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Employee;
use App\Models\Branch;
use App\Models\Media;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\Hash;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        $branches = Branch::all();
        $managerUser = User::where('username', 'manager')->first();
        $staffUser = User::where('username', 'staff')->first();

        $employees = [];

        if ($managerUser && $branches->count() > 0) {
            $employees[] = [
                'user_id' => $managerUser->id,
                'branch_id' => $branches->first()->id,
                'name' => 'Manager Zard',
                'gender' => 'M',
                'status' => 'active',
                'join_date' => now()->subYear(),
                'phone' => '08123456789',
                'email' => 'manager@zard.com'
            ];
        }

        if ($staffUser && $branches->count() > 1) {
            $employees[] = [
                'user_id' => $staffUser->id,
                'branch_id' => $branches->get(1)->id,
                'name' => 'Staff Zard',
                'gender' => 'F',
                'status' => 'active',
                'join_date' => now()->subMonths(6),
                'phone' => '08123456780',
                'email' => 'staff@zard.com'
            ];
        }

        // Create additional users and employees
        for ($i = 0; $i < 10; $i++) {
            $user = User::updateOrCreate(
                ['username' => 'employee' . ($i + 1)],
                [
                    'password' => Hash::make('password'),
                    'role' => 'staff'
                ]
            );

            $employees[] = [
                'user_id' => $user->id,
                'branch_id' => $branches->random()->id,
                'name' => $faker->name(),
                'gender' => $faker->randomElement(['M', 'F']),
                'status' => $faker->randomElement(['active', 'inactive']),
                'join_date' => $faker->dateTimeBetween('-2 years', 'now'),
                'phone' => $faker->phoneNumber(),
                'email' => $faker->unique()->safeEmail(),
            ];
        }

        foreach ($employees as $empData) {
            $employee = Employee::updateOrCreate(
                ['user_id' => $empData['user_id']],
                $empData
            );

            // Create dummy media (profile image) for each employee if not exists
            if (!$employee->media()->exists()) {
                Media::create([
                    'model_type' => Employee::class,
                    'model_id' => $employee->id,
                    'file_name' => 'employee_' . $employee->id . '.jpg',
                    'mime_type' => 'image/jpeg',
                    'extension' => 'jpg',
                    'size' => $faker->numberBetween(10000, 50000),
                    'disk' => 'public',
                    'path' => 'employees/' . $employee->id . '.jpg',
                ]);
            }
        }
    }
}
