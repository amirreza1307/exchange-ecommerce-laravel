<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Currency;
use App\Models\Transaction;
use App\Models\Wallet;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $testUser = User::where('email', 'user@exchange.com')->first();
        $adminUser = User::where('email', 'admin@exchange.com')->first();
        
        if (!$testUser || !$adminUser) {
            return;
        }

        $irr = Currency::where('symbol', 'IRR')->first();
        $btc = Currency::where('symbol', 'BTC')->first();
        $eth = Currency::where('symbol', 'ETH')->first();
        $usdt = Currency::where('symbol', 'USDT')->first();

        // Deposit transactions
        Transaction::create([
            'user_id' => $testUser->id,
            'currency_id' => $irr->id,
            'type' => 'deposit',
            'amount' => 50000000, // 50M IRR
            'fee' => 0,
            'final_amount' => 50000000,
            'status' => 'completed',
            'reference_id' => 'DEP' . uniqid(),
            'description' => 'Initial deposit',
            'processed_at' => now()->subDays(35),
            'created_at' => now()->subDays(35),
            'updated_at' => now()->subDays(35)
        ]);

        // Buy transaction (BTC)
        Transaction::create([
            'user_id' => $testUser->id,
            'currency_id' => $btc->id,
            'type' => 'buy',
            'amount' => 0.01,
            'fee' => 0.00005, // 0.5% fee
            'final_amount' => 0.00995,
            'status' => 'completed',
            'reference_id' => 'BUY' . uniqid(),
            'description' => 'Buy Bitcoin',
            'metadata' => json_encode(['order_id' => 1]),
            'processed_at' => now()->subDays(30),
            'created_at' => now()->subDays(30),
            'updated_at' => now()->subDays(30)
        ]);

        // Buy transaction (ETH)
        Transaction::create([
            'user_id' => $testUser->id,
            'currency_id' => $eth->id,
            'type' => 'buy',
            'amount' => 0.05,
            'fee' => 0.00025, // 0.5% fee
            'final_amount' => 0.04975,
            'status' => 'completed',
            'reference_id' => 'BUY' . uniqid(),
            'description' => 'Buy Ethereum',
            'metadata' => json_encode(['order_id' => 2]),
            'processed_at' => now()->subDays(25),
            'created_at' => now()->subDays(25),
            'updated_at' => now()->subDays(25)
        ]);

        // Sell transaction (USDT to IRR)
        Transaction::create([
            'user_id' => $testUser->id,
            'currency_id' => $irr->id,
            'type' => 'sell',
            'amount' => 6450000, // Amount received in IRR
            'fee' => 12900, // 0.2% fee
            'final_amount' => 6437100,
            'status' => 'completed',
            'reference_id' => 'SELL' . uniqid(),
            'description' => 'Sell USDT for IRR',
            'metadata' => json_encode(['order_id' => 3]),
            'processed_at' => now()->subDays(20),
            'created_at' => now()->subDays(20),
            'updated_at' => now()->subDays(20)
        ]);

        // Exchange transaction (BTC to USDT)
        Transaction::create([
            'user_id' => $testUser->id,
            'currency_id' => $usdt->id,
            'type' => 'exchange',
            'amount' => 67,
            'fee' => 0.335, // 0.5% fee
            'final_amount' => 66.665,
            'status' => 'completed',
            'reference_id' => 'EXC' . uniqid(),
            'description' => 'Exchange BTC to USDT',
            'metadata' => json_encode(['order_id' => 4]),
            'processed_at' => now()->subDays(15),
            'created_at' => now()->subDays(15),
            'updated_at' => now()->subDays(15)
        ]);

        // Withdrawal transaction
        Transaction::create([
            'user_id' => $testUser->id,
            'currency_id' => $irr->id,
            'type' => 'withdraw',
            'amount' => 10000000, // 10M IRR
            'fee' => 50000, // 0.5% fee
            'final_amount' => 9950000,
            'status' => 'completed',
            'reference_id' => 'WTH' . uniqid(),
            'description' => 'Withdrawal to bank account',
            'processed_at' => now()->subDays(10),
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10)
        ]);

        // Pending withdrawal
        Transaction::create([
            'user_id' => $testUser->id,
            'currency_id' => $irr->id,
            'type' => 'withdraw',
            'amount' => 5000000, // 5M IRR
            'fee' => 25000, // 0.5% fee
            'final_amount' => 4975000,
            'status' => 'pending',
            'reference_id' => 'WTH' . uniqid(),
            'description' => 'Pending withdrawal',
            'created_at' => now()->subHours(1),
            'updated_at' => now()->subHours(1)
        ]);

        // Transfer between wallets (internal) - Remove this as transfer is not a valid type

        // Admin deposit transaction for testing
        Transaction::create([
            'user_id' => $adminUser->id,
            'currency_id' => $irr->id,
            'type' => 'deposit',
            'amount' => 100000000, // 100M IRR
            'fee' => 0,
            'final_amount' => 100000000,
            'status' => 'completed',
            'reference_id' => 'DEP' . uniqid(),
            'description' => 'Initial admin deposit',
            'processed_at' => now()->subDays(40),
            'created_at' => now()->subDays(40),
            'updated_at' => now()->subDays(40)
        ]);

        // Failed transaction
        Transaction::create([
            'user_id' => $testUser->id,
            'currency_id' => $btc->id,
            'type' => 'buy',
            'amount' => 0.023,
            'fee' => 0.000115,
            'final_amount' => 0.022885,
            'status' => 'failed',
            'reference_id' => 'BUY' . uniqid(),
            'description' => 'Failed buy order - insufficient balance',
            'metadata' => json_encode(['order_id' => 7, 'error' => 'insufficient_balance']),
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5)
        ]);
    }
}