<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LoyaltyReward;

class LoyaltyRewardsTableSeeder extends Seeder
{
    public function run(): void
    {
        $rewards = [
            [
                'name' => '10% Service Discount',
                'description' => 'Get 10% off your next service booking',
                'points_required' => 100,
                'type' => 'discount',
                'value' => 10.00,
                'code' => 'LOYALTY10',
                'is_active' => true,
                'max_redemptions' => 1000,
                'current_redemptions' => 0,
                'valid_from' => now(),
                'valid_until' => now()->addYear(),
            ],
            [
                'name' => 'Free Basic Cleaning',
                'description' => 'Redeem for a free basic cleaning service',
                'points_required' => 250,
                'type' => 'free_service',
                'value' => null,
                'code' => 'FREECLEAN',
                'is_active' => true,
                'max_redemptions' => 500,
                'current_redemptions' => 0,
                'valid_from' => now(),
                'valid_until' => now()->addYear(),
            ],
            [
                'name' => 'Premium Upgrade',
                'description' => 'Upgrade your service to premium for $20 off',
                'points_required' => 150,
                'type' => 'upgrade',
                'value' => 20.00,
                'code' => 'PREMIUM20',
                'is_active' => true,
                'max_redemptions' => 300,
                'current_redemptions' => 0,
                'valid_from' => now(),
                'valid_until' => now()->addYear(),
            ],
            [
                'name' => '$5 Cash Back',
                'description' => 'Get $5 back to your wallet',
                'points_required' => 75,
                'type' => 'cashback',
                'value' => 5.00,
                'code' => 'CASHBACK5',
                'is_active' => true,
                'max_redemptions' => 2000,
                'current_redemptions' => 0,
                'valid_from' => now(),
                'valid_until' => now()->addYear(),
            ],
            [
                'name' => '25% Off Maintenance',
                'description' => 'Get 25% off maintenance services',
                'points_required' => 200,
                'type' => 'discount',
                'value' => 25.00,
                'code' => 'MAINT25',
                'is_active' => true,
                'max_redemptions' => 400,
                'current_redemptions' => 0,
                'valid_from' => now(),
                'valid_until' => now()->addYear(),
            ],
        ];

        foreach ($rewards as $reward) {
            LoyaltyReward::create($reward);
        }
    }
}

