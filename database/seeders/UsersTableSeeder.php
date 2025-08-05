<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UsersTableSeeder extends Seeder {
    public function run(): void {
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'phone' => '1234567890',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'wallet_balance' => 100.00
        ]);

        User::create([
            'name' => 'Customer User',
            'email' => 'customer@example.com',
            'phone' => '0987654321',
            'password' => Hash::make('password'),
            'role' => 'customer',
            'wallet_balance' => 50.00
        ]);
    }
}