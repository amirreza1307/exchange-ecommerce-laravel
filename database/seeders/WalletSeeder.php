<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Currency;
use App\Models\Wallet;

class WalletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $currencies = Currency::all();

        foreach ($users as $user) {
            foreach ($currencies as $currency) {
                // Skip IRR for now as it's in user's rial_balance
                if ($currency->symbol === 'IRR') {
                    continue;
                }

                // Use updateOrCreate to avoid duplicate key errors
                Wallet::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'currency_id' => $currency->id,
                    ],
                    [
                        'balance' => $this->getInitialBalance($user, $currency),
                        'frozen_balance' => 0
                    ]
                );
            }
        }
    }

    private function getInitialBalance($user, $currency)
    {
        // Give admin some balance for testing
        if ($user->role === 'admin') {
            return match ($currency->symbol) {
                'BTC' => 0.5,
                'ETH' => 10,
                'USDT' => 50000,
                'BNB' => 20,
                'ADA' => 10000,
                'DOT' => 500,
                'LTC' => 50,
                default => 0
            };
        }

        // Give test user some balance
        if ($user->email === 'user@exchange.com') {
            return match ($currency->symbol) {
                'BTC' => 0.01,
                'ETH' => 0.5,
                'USDT' => 1000,
                'BNB' => 2,
                'ADA' => 500,
                'DOT' => 10,
                'LTC' => 1,
                default => 0
            };
        }

        return 0;
    }
}