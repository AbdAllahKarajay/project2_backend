<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Faker\Factory as Faker;

class UsersTableSeeder extends Seeder {
    public function run(): void {
        $faker = Faker::create();

        // Admin user
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'phone' => '1234567890',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'wallet_balance' => 250.00,
            ]
        );

        // Seed a realistic set of customers
        User::updateOrCreate(
            ['email' => $faker->unique()->safeEmail()],
            [
                'name' => 'Abdulrahman',
                'phone' => '0987654321',
                'password' => Hash::make('password'),
                'role' => 'customer',
                'wallet_balance' => $faker->randomFloat(2, 0, 500),
            ]
        );
        $numCustomers = 20;
        for ($i = 0; $i < $numCustomers; $i++) {
            $name = $faker->name();
            User::updateOrCreate(
                ['email' => $faker->unique()->safeEmail()],
                [
                    'name' => $name,
                    'phone' => $faker->unique()->numerify('##########'),
                    'password' => Hash::make('password'),
                    'role' => 'customer',
                    'wallet_balance' => $faker->randomFloat(2, 0, 500),
                ]
            );
        }
    }
}