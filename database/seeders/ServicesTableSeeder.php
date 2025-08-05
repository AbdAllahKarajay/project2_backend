<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Service;

class ServicesTableSeeder extends Seeder {
    public function run(): void {
        Service::create([
            'name' => 'Home Cleaning',
            'description' => 'General house cleaning service.',
            'category' => 'Cleaning',
            'base_price' => 100.00,
            'duration_minutes' => 90,
            'average_rating' => 4.5
        ]);

        Service::create([
            'name' => 'Office Cleaning',
            'description' => 'Cleaning for small offices.',
            'category' => 'Cleaning',
            'base_price' => 150.00,
            'duration_minutes' => 120,
            'average_rating' => 4.7
        ]);
    }
}