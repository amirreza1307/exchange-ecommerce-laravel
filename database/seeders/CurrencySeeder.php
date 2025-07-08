<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Currency;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencies = [
            [
                'symbol' => 'IRR',
                'name' => 'Iranian Rial',
                'description' => 'Iranian Rial - Base currency',
                'buy_price' => 1,
                'sell_price' => 1,
                'buy_commission' => 0,
                'sell_commission' => 0,
                'treasury_balance' => 1000000000, // 1 billion IRR
                'decimal_places' => 0,
                'is_active' => true,
                'is_tradeable' => false, // IRR is not tradeable directly
            ],
            [
                'symbol' => 'BTC',
                'name' => 'Bitcoin',
                'description' => 'Bitcoin - The first cryptocurrency',
                'buy_price' => 4350000000, // 4.35 billion IRR
                'sell_price' => 4320000000, // 4.32 billion IRR
                'buy_commission' => 0.5,
                'sell_commission' => 0.5,
                'treasury_balance' => 10,
                'decimal_places' => 8,
                'is_active' => true,
                'is_tradeable' => true,
            ],
            [
                'symbol' => 'ETH',
                'name' => 'Ethereum',
                'description' => 'Ethereum - Smart contract platform',
                'buy_price' => 180000000, // 180 million IRR
                'sell_price' => 178000000, // 178 million IRR
                'buy_commission' => 0.5,
                'sell_commission' => 0.5,
                'treasury_balance' => 100,
                'decimal_places' => 8,
                'is_active' => true,
                'is_tradeable' => true,
            ],
            [
                'symbol' => 'USDT',
                'name' => 'Tether',
                'description' => 'USDT - Stable coin pegged to USD',
                'buy_price' => 65000, // 65,000 IRR
                'sell_price' => 64500, // 64,500 IRR
                'buy_commission' => 0.2,
                'sell_commission' => 0.2,
                'treasury_balance' => 10000,
                'decimal_places' => 6,
                'is_active' => true,
                'is_tradeable' => true,
            ],
            [
                'symbol' => 'BNB',
                'name' => 'Binance Coin',
                'description' => 'BNB - Binance exchange token',
                'buy_price' => 42000000, // 42 million IRR
                'sell_price' => 41500000, // 41.5 million IRR
                'buy_commission' => 0.3,
                'sell_commission' => 0.3,
                'treasury_balance' => 500,
                'decimal_places' => 8,
                'is_active' => true,
                'is_tradeable' => true,
            ],
            [
                'symbol' => 'ADA',
                'name' => 'Cardano',
                'description' => 'ADA - Cardano blockchain token',
                'buy_price' => 25000, // 25,000 IRR
                'sell_price' => 24500, // 24,500 IRR
                'buy_commission' => 0.4,
                'sell_commission' => 0.4,
                'treasury_balance' => 50000,
                'decimal_places' => 6,
                'is_active' => true,
                'is_tradeable' => true,
            ],
            [
                'symbol' => 'DOT',
                'name' => 'Polkadot',
                'description' => 'DOT - Polkadot network token',
                'buy_price' => 320000, // 320,000 IRR
                'sell_price' => 315000, // 315,000 IRR
                'buy_commission' => 0.3,
                'sell_commission' => 0.3,
                'treasury_balance' => 5000,
                'decimal_places' => 6,
                'is_active' => true,
                'is_tradeable' => true,
            ],
            [
                'symbol' => 'LTC',
                'name' => 'Litecoin',
                'description' => 'LTC - Litecoin, silver to Bitcoin\'s gold',
                'buy_price' => 6500000, // 6.5 million IRR
                'sell_price' => 6400000, // 6.4 million IRR
                'buy_commission' => 0.4,
                'sell_commission' => 0.4,
                'treasury_balance' => 200,
                'decimal_places' => 8,
                'is_active' => true,
                'is_tradeable' => true,
            ],
        ];

        foreach ($currencies as $currency) {
            Currency::create($currency);
        }
    }
}
