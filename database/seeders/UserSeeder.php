<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin User
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'password' => Hash::make('password'),
                'role' => 'admin'
            ]
        );

        // Manager User
        User::updateOrCreate(
            ['username' => 'manager'],
            [
                'password' => Hash::make('password'),
                'role' => 'manager'
            ]
        );

        // Staff User
        User::updateOrCreate(
            ['username' => 'staff'],
            [
                'password' => Hash::make('password'),
                'role' => 'staff'
            ]
        );
    }
}
