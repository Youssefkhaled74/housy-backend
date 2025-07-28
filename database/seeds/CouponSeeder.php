<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $coupons = [
            [
                'user_id' => 15, // Ensure this user_id exists in the users table
                'type' => 'fixed',
                'code' => 'SAVE10',
                'details' => 'Fixed discount of $10',
                'discount' => 10.00,
                'discount_type' => 'fixed',
                'start_date' => now()->subDays(1),
                'end_date' => now()->addDays(30),
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 18,
                'type' => 'percentage',
                'code' => 'PERCENT20',
                'details' => '20% off on all items',
                'discount' => 20.00,
                'discount_type' => 'percentage',
                'start_date' => now()->subDays(1),
                'end_date' => now()->addDays(15),
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($coupons as $coupon) {
            Coupon::create($coupon);
        }
    }
}