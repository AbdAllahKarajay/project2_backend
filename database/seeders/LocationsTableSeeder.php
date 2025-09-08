<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Location;
use App\Models\User;
use Faker\Factory as Faker;

class LocationsTableSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        // Get or create users to attach locations
        $users = User::all();
        if ($users->isEmpty()) {
            $users = collect([
                User::create([
                    'name' => 'Seeded Customer',
                    'email' => 'seeded-customer@example.com',
                    'phone' => '1002003000',
                    'password' => bcrypt('password'),
                    'role' => 'customer',
                    'wallet_balance' => 50.00
                ])
            ]);
        }

        foreach ($users as $user) {
            // Each user gets 1-3 locations around a central city coordinate
            $numLocations = $faker->numberBetween(1, 3);

            // Pick a base coordinate (simulate a few cities)
            $cities = [
                ['name' => 'New York', 'lat' => 40.7128, 'lng' => -74.0060],
                ['name' => 'Los Angeles', 'lat' => 34.0522, 'lng' => -118.2437],
                ['name' => 'Chicago', 'lat' => 41.8781, 'lng' => -87.6298],
                ['name' => 'Houston', 'lat' => 29.7604, 'lng' => -95.3698],
                ['name' => 'Miami', 'lat' => 25.7617, 'lng' => -80.1918],
            ];
            $city = $faker->randomElement($cities);

            for ($i = 0; $i < $numLocations; $i++) {
                $offsetLat = $faker->randomFloat(4, -0.05, 0.05);
                $offsetLng = $faker->randomFloat(4, -0.05, 0.05);

                Location::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'address_text' => $faker->streetAddress() . ', ' . $city['name'],
                    ],
                    [
                        'latitude' => $city['lat'] + $offsetLat,
                        'longitude' => $city['lng'] + $offsetLng,
                    ]
                );
            }
        }
    }
} 