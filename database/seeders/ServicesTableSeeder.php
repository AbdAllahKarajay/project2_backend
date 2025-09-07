<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Service;
use Faker\Factory as Faker;

class ServicesTableSeeder extends Seeder {
    public function run(): void {
        $faker = Faker::create();

        $services = [
            ['name' => 'Home Cleaning', 'category' => 'Cleaning', 'duration' => [60, 180]],
            ['name' => 'Office Cleaning', 'category' => 'Cleaning', 'duration' => [90, 240]],
            ['name' => 'Deep Cleaning', 'category' => 'Cleaning', 'duration' => [120, 300]],
            ['name' => 'Window Washing', 'category' => 'Cleaning', 'duration' => [45, 120]],
            ['name' => 'Carpet Shampoo', 'category' => 'Cleaning', 'duration' => [60, 180]],
            ['name' => 'Move-In/Out Cleaning', 'category' => 'Cleaning', 'duration' => [120, 360]],
            ['name' => 'Appliance Cleaning', 'category' => 'Cleaning', 'duration' => [30, 90]],
            ['name' => 'Garden Maintenance', 'category' => 'Maintenance', 'duration' => [60, 240]],
            ['name' => 'HVAC Checkup', 'category' => 'Maintenance', 'duration' => [60, 120]],
            ['name' => 'Plumbing Inspection', 'category' => 'Maintenance', 'duration' => [60, 180]],
            ['name' => 'Electrical Safety Check', 'category' => 'Maintenance', 'duration' => [60, 120]],
            ['name' => 'Pest Control', 'category' => 'Maintenance', 'duration' => [60, 180]],
        ];

        foreach ($services as $svc) {
            Service::updateOrCreate(
                ['name' => $svc['name']],
                [
                    'description' => $faker->sentence(12),
                    'category' => $svc['category'],
                    'base_price' => $faker->randomFloat(2, 40, 400),
                    'duration_minutes' => $faker->numberBetween($svc['duration'][0], $svc['duration'][1]),
                    'average_rating' => $faker->randomFloat(1, 3.5, 5.0),
                ]
            );
        }
    }
}