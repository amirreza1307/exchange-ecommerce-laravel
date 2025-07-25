<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Currency;
use App\Models\Order;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $testUser = User::where('email', 'user@exchange.com')->first();
        if (!$testUser) {
            return;
        }

        $irr = Currency::where('symbol', 'IRR')->first();
        $btc = Currency::where('symbol', 'BTC')->first();
        $eth = Currency::where('symbol', 'ETH')->first();
        $usdt = Currency::where('symbol', 'USDT')->first();

        // Create completed buy orders
        Order::create([
            'user_id' => $testUser->id,
            'type' => 'buy',
            'from_currency_id' => $irr->id,
            'to_currency_id' => $btc->id,
            'from_amount' => 43500000, // 43.5M IRR
            'to_amount' => 0.01,
            'exchange_rate' => 4350000000,
            'commission_rate' => 0.5,
            'commission_amount' => 217500, // 0.5% fee
            'final_amount' => 0.01,
            'status' => 'completed',
            'created_at' => now()->subDays(30),
            'updated_at' => now()->subDays(30),
            'processed_at' => now()->subDays(30)
        ]);

        Order::create([
            'user_id' => $testUser->id,
            'type' => 'buy',
            'from_currency_id' => $irr->id,
            'to_currency_id' => $eth->id,
            'from_amount' => 9000000, // 9M IRR
            'to_amount' => 0.05,
            'exchange_rate' => 180000000,
            'commission_rate' => 0.5,
            'commission_amount' => 45000, // 0.5% fee
            'final_amount' => 0.05,
            'status' => 'completed',
            'created_at' => now()->subDays(25),
            'updated_at' => now()->subDays(25),
            'processed_at' => now()->subDays(25)
        ]);

        // Create completed sell order
        Order::create([
            'user_id' => $testUser->id,
            'type' => 'sell',
            'from_currency_id' => $usdt->id,
            'to_currency_id' => $irr->id,
            'from_amount' => 100,
            'to_amount' => 6450000, // 6.45M IRR
            'exchange_rate' => 64500,
            'commission_rate' => 0.2,
            'commission_amount' => 12900, // 0.2% fee on IRR
            'final_amount' => 6437100, // 6.45M - 12.9K fee
            'status' => 'completed',
            'created_at' => now()->subDays(20),
            'updated_at' => now()->subDays(20),
            'processed_at' => now()->subDays(20)
        ]);

        // Create exchange order
        Order::create([
            'user_id' => $testUser->id,
            'type' => 'exchange',
            'from_currency_id' => $btc->id,
            'to_currency_id' => $usdt->id,
            'from_amount' => 0.001,
            'to_amount' => 67, // Approximate BTC to USDT
            'exchange_rate' => 67000,
            'commission_rate' => 0.5,
            'commission_amount' => 0.335, // 0.5% fee
            'final_amount' => 66.665,
            'status' => 'completed',
            'created_at' => now()->subDays(15),
            'updated_at' => now()->subDays(15),
            'processed_at' => now()->subDays(15)
        ]);

        // Create pending order
        Order::create([
            'user_id' => $testUser->id,
            'type' => 'buy',
            'from_currency_id' => $irr->id,
            'to_currency_id' => $btc->id,
            'from_amount' => 21750000, // 21.75M IRR
            'to_amount' => 0.005,
            'exchange_rate' => 4350000000,
            'commission_rate' => 0.5,
            'commission_amount' => 108750, // 0.5% fee
            'final_amount' => 0.005,
            'status' => 'pending',
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2)
        ]);

        // Create cancelled order
        Order::create([
            'user_id' => $testUser->id,
            'type' => 'sell',
            'from_currency_id' => $eth->id,
            'to_currency_id' => $irr->id,
            'from_amount' => 0.1,
            'to_amount' => 17800000, // 17.8M IRR
            'exchange_rate' => 178000000,
            'commission_rate' => 0.2,
            'commission_amount' => 35600, // 0.2% fee
            'final_amount' => 17764400,
            'status' => 'cancelled',
            'cancellation_reason' => 'User cancelled the order',
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10)
        ]);

        // Create failed order
        Order::create([
            'user_id' => $testUser->id,
            'type' => 'buy',
            'from_currency_id' => $irr->id,
            'to_currency_id' => $btc->id,
            'from_amount' => 100000000, // 100M IRR
            'to_amount' => 0.023,
            'exchange_rate' => 4350000000,
            'commission_rate' => 0.5,
            'commission_amount' => 500000, // 0.5% fee
            'final_amount' => 0.023,
            'status' => 'failed',
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5)
        ]);
    }
}