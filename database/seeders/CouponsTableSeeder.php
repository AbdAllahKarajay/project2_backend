<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Coupon;

class CouponsTableSeeder extends Seeder {
    public function run(): void {
        Coupon::create([
            'code' => 'WELCOME10',
            'type' => 'percentage',
            'value' => 10,
            'min_spend' => 50,
            'expiry_date' => now()->addDays(30),
            'usage_limit' => 100
        ]);

        Coupon::create([
            'code' => 'FLAT20',
            'type' => 'fixed',
            'value' => 20,
            'min_spend' => 100,
            'expiry_date' => now()->addDays(10),
            'usage_limit' => 50
        ]);
    }
}