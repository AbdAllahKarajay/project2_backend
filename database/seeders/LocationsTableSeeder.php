<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Location;
use App\Models\User;

class LocationsTableSeeder extends Seeder
{
    public function run(): void
    {
        // Get existing users
        $users = User::all();

        if ($users->isEmpty()) {
            // Create a default user if none exist
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'phone' => '1234567890',
                'password' => bcrypt('password'),
                'role' => 'customer',
                'wallet_balance' => 100.00
            ]);
        } else {
            $user = $users->first();
        }

        // Create sample locations
        Location::create([
            'user_id' => $user->id,
            'address_text' => '123 Main Street, Downtown, City Center',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        Location::create([
            'user_id' => $user->id,
            'address_text' => '456 Oak Avenue, Suburb District',
            'latitude' => 40.7589,
            'longitude' => -73.9851,
        ]);

        Location::create([
            'user_id' => $user->id,
            'address_text' => '789 Pine Street, Residential Area',
            'latitude' => 40.7505,
            'longitude' => -73.9934,
        ]);
    }
} 