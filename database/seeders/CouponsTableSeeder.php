<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Coupon;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class CouponsTableSeeder extends Seeder {
    public function run(): void {
        $faker = Faker::create();

        $staticCoupons = [
            [
                'code' => 'WELCOME10',
                'type' => 'percentage',
                'value' => 10,
                'min_spend' => 50,
                'expiry_date' => now()->addDays(45),
                'usage_limit' => 500,
            ],
            [
                'code' => 'FLAT20',
                'type' => 'fixed',
                'value' => 20,
                'min_spend' => 120,
                'expiry_date' => now()->addDays(20),
                'usage_limit' => 200,
            ],
        ];

        foreach ($staticCoupons as $cp) {
            Coupon::updateOrCreate(['code' => $cp['code']], $cp);
        }

        // Generate some random seasonal coupons
        for ($i = 0; $i < 5; $i++) {
            $types = ['percentage', 'fixed'];
            $type = $faker->randomElement($types);
            $value = $type === 'percentage' ? $faker->numberBetween(5, 40) : $faker->numberBetween(5, 50);
            Coupon::updateOrCreate(
                ['code' => Str::upper($faker->unique()->bothify('SAVE##??'))],
                [
                    'type' => $type,
                    'value' => $value,
                    'min_spend' => $faker->numberBetween(20, 200),
                    'expiry_date' => now()->addDays($faker->numberBetween(7, 120)),
                    'usage_limit' => $faker->numberBetween(50, 1000),
                ]
            );
        }
    }
}