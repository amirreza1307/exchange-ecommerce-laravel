<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Discount;
use App\Models\Currency;
use App\Models\User;

class DiscountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Active percentage discount for all currencies
        Discount::create([
            'code' => 'WELCOME10',
            'title' => 'Welcome Discount - 10% off',
            'type' => 'percentage',
            'value' => 10,
            'min_order_amount' => 1000000, // 1M IRR minimum
            'max_discount_amount' => 500000, // Max 500K IRR discount
            'usage_limit' => 100,
            'used_count' => 5,
            'user_usage_limit' => 1,
            'is_active' => true,
            'expires_at' => now()->addMonths(3)
        ]);

        // Active fixed amount discount
        Discount::create([
            'code' => 'SAVE50K',
            'title' => 'Save 50,000 IRR',
            'type' => 'fixed',
            'value' => 50000,
            'min_order_amount' => 500000, // 500K IRR minimum
            'usage_limit' => 50,
            'used_count' => 10,
            'user_usage_limit' => 2,
            'is_active' => true,
            'expires_at' => now()->addMonth()
        ]);

        // Currency-specific discount (BTC only)
        Discount::create([
            'code' => 'BTCSPECIAL',
            'title' => 'Bitcoin Special - 5% off',
            'description' => 'Special discount for Bitcoin purchases only',
            'type' => 'percentage',
            'value' => 5,
            'min_order_amount' => 10000000, // 10M IRR minimum
            'usage_limit' => 20,
            'used_count' => 2,
            'is_active' => true,
            'expires_at' => now()->addWeeks(2)
        ]);

        // User-specific discount
        Discount::create([
            'code' => 'VIP20',
            'title' => 'VIP Customer - 20% off',
            'description' => 'Exclusive discount for VIP customers',
            'type' => 'percentage',
            'value' => 20,
            'min_order_amount' => 5000000, // 5M IRR minimum
            'max_discount_amount' => 2000000, // Max 2M IRR discount
            'usage_limit' => 5,
            'used_count' => 1,
            'user_usage_limit' => 5,
            'is_active' => true,
            'expires_at' => now()->addMonths(6)
        ]);

        // Expired discount
        Discount::create([
            'code' => 'EXPIRED2023',
            'title' => 'New Year 2023 Discount',
            'type' => 'percentage',
            'value' => 15,
            'min_order_amount' => 1000000,
            'usage_limit' => 1000,
            'used_count' => 500,
            'is_active' => true,
            'expires_at' => now()->subMonth() // Already expired
        ]);

        // Inactive discount
        Discount::create([
            'code' => 'INACTIVE',
            'title' => 'Inactive Promotion',
            'type' => 'fixed',
            'value' => 100000,
            'usage_limit' => 10,
            'used_count' => 0,
            'is_active' => false,
            'expires_at' => now()->addYear()
        ]);

        // ETH specific discount
        Discount::create([
            'code' => 'ETHBONUS',
            'title' => 'Ethereum Bonus - 3% off',
            'description' => 'Special bonus for Ethereum purchases',
            'type' => 'percentage',
            'value' => 3,
            'min_order_amount' => 5000000, // 5M IRR minimum
            'usage_limit' => 30,
            'used_count' => 0,
            'is_active' => true,
            'expires_at' => now()->addMonth()
        ]);

        // No minimum order amount discount
        Discount::create([
            'code' => 'TRYNOW',
            'title' => 'Try Now - 2% off any order',
            'type' => 'percentage',
            'value' => 2,
            'min_order_amount' => 0, // No minimum
            'usage_limit' => 200,
            'used_count' => 50,
            'user_usage_limit' => 3,
            'is_active' => true,
            'expires_at' => now()->addWeek()
        ]);

        // Fully used discount
        Discount::create([
            'code' => 'SOLDOUT',
            'title' => 'Limited Offer - Sold Out',
            'type' => 'percentage',
            'value' => 25,
            'min_order_amount' => 1000000,
            'usage_limit' => 10,
            'used_count' => 10, // Fully used
            'is_active' => true,
            'expires_at' => now()->addMonth()
        ]);
    }
}