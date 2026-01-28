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
        User::create([
            'name' => 'Zard Admin',
            'email' => 'admin@zardstore.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'branch_id' => 1,
        ]);

        // Employee/Cashier
        User::create([
            'name' => 'Medan Cashier',
            'email' => 'staff1@zardstore.com',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'branch_id' => 1,
        ]);
    }
}
