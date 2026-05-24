<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        $coupons = [
            [
                'code' => 'WELCOME10',
                'type' => 'percentage',
                'value' => 10.00,
                'min_order_amount' => null,
                'is_active' => true,
                'description' => '10% off on your order',
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addYear(),
            ],
            [
                'code' => 'FLAT20',
                'type' => 'fixed',
                'value' => 20.00,
                'min_order_amount' => 100.00,
                'is_active' => true,
                'description' => 'Flat $20 off on orders of $100 or more',
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addYear(),
            ],
        ];

        foreach ($coupons as $coupon) {
            Coupon::updateOrCreate(['code' => $coupon['code']], $coupon);
        }
    }
}
