<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Currency;
use App\Models\ExchangeRate;

class ExchangeRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencies = Currency::all()->keyBy('symbol');
        
        if ($currencies->isEmpty()) {
            return;
        }

        $irr = $currencies->get('IRR');
        $btc = $currencies->get('BTC');
        $eth = $currencies->get('ETH');
        $usdt = $currencies->get('USDT');
        $bnb = $currencies->get('BNB');
        $ada = $currencies->get('ADA');
        $dot = $currencies->get('DOT');
        $ltc = $currencies->get('LTC');

        // Exchange rates between cryptocurrencies (not involving IRR)
        $exchangeRates = [
            // BTC to other cryptos
            [
                'from_currency_id' => $btc->id,
                'to_currency_id' => $eth->id,
                'rate' => 24.17, // 1 BTC = 24.17 ETH
                'buy_rate' => 24.17,
                'sell_rate' => 24.10,
                'is_active' => true
            ],
            [
                'from_currency_id' => $btc->id,
                'to_currency_id' => $usdt->id,
                'rate' => 67000, // 1 BTC = 67,000 USDT
                'buy_rate' => 67000,
                'sell_rate' => 66800,
                'is_active' => true
            ],
            [
                'from_currency_id' => $btc->id,
                'to_currency_id' => $bnb->id,
                'rate' => 103.57, // 1 BTC = 103.57 BNB
                'buy_rate' => 103.57,
                'sell_rate' => 103.20,
                'is_active' => true
            ],

            // ETH to other cryptos
            [
                'from_currency_id' => $eth->id,
                'to_currency_id' => $btc->id,
                'rate' => 0.0414, // 1 ETH = 0.0414 BTC
                'buy_rate' => 0.0414,
                'sell_rate' => 0.0412,
                'is_active' => true
            ],
            [
                'from_currency_id' => $eth->id,
                'to_currency_id' => $usdt->id,
                'rate' => 2770, // 1 ETH = 2,770 USDT
                'buy_rate' => 2770,
                'sell_rate' => 2765,
                'is_active' => true
            ],
            [
                'from_currency_id' => $eth->id,
                'to_currency_id' => $bnb->id,
                'rate' => 4.28, // 1 ETH = 4.28 BNB
                'buy_rate' => 4.28,
                'sell_rate' => 4.26,
                'is_active' => true
            ],

            // USDT to other cryptos
            [
                'from_currency_id' => $usdt->id,
                'to_currency_id' => $btc->id,
                'rate' => 0.0000149, // 1 USDT = 0.0000149 BTC
                'buy_rate' => 0.0000149,
                'sell_rate' => 0.0000148,
                'is_active' => true
            ],
            [
                'from_currency_id' => $usdt->id,
                'to_currency_id' => $eth->id,
                'rate' => 0.000361, // 1 USDT = 0.000361 ETH
                'buy_rate' => 0.000361,
                'sell_rate' => 0.000360,
                'is_active' => true
            ],
            [
                'from_currency_id' => $usdt->id,
                'to_currency_id' => $bnb->id,
                'rate' => 0.00155, // 1 USDT = 0.00155 BNB
                'buy_rate' => 0.00155,
                'sell_rate' => 0.00154,
                'is_active' => true
            ],

            // BNB to other cryptos
            [
                'from_currency_id' => $bnb->id,
                'to_currency_id' => $btc->id,
                'rate' => 0.00966, // 1 BNB = 0.00966 BTC
                'buy_rate' => 0.00966,
                'sell_rate' => 0.00964,
                'is_active' => true
            ],
            [
                'from_currency_id' => $bnb->id,
                'to_currency_id' => $eth->id,
                'rate' => 0.233, // 1 BNB = 0.233 ETH
                'buy_rate' => 0.233,
                'sell_rate' => 0.232,
                'is_active' => true
            ],
            [
                'from_currency_id' => $bnb->id,
                'to_currency_id' => $usdt->id,
                'rate' => 646, // 1 BNB = 646 USDT
                'buy_rate' => 646,
                'sell_rate' => 644,
                'is_active' => true
            ],

            // ADA exchange rates
            [
                'from_currency_id' => $ada->id,
                'to_currency_id' => $usdt->id,
                'rate' => 0.385, // 1 ADA = 0.385 USDT
                'buy_rate' => 0.385,
                'sell_rate' => 0.384,
                'is_active' => true
            ],
            [
                'from_currency_id' => $usdt->id,
                'to_currency_id' => $ada->id,
                'rate' => 2.597, // 1 USDT = 2.597 ADA
                'buy_rate' => 2.597,
                'sell_rate' => 2.590,
                'is_active' => true
            ],

            // DOT exchange rates
            [
                'from_currency_id' => $dot->id,
                'to_currency_id' => $usdt->id,
                'rate' => 4.923, // 1 DOT = 4.923 USDT
                'buy_rate' => 4.923,
                'sell_rate' => 4.910,
                'is_active' => true
            ],
            [
                'from_currency_id' => $usdt->id,
                'to_currency_id' => $dot->id,
                'rate' => 0.203, // 1 USDT = 0.203 DOT
                'buy_rate' => 0.203,
                'sell_rate' => 0.202,
                'is_active' => true
            ],

            // LTC exchange rates
            [
                'from_currency_id' => $ltc->id,
                'to_currency_id' => $btc->id,
                'rate' => 0.00149, // 1 LTC = 0.00149 BTC
                'buy_rate' => 0.00149,
                'sell_rate' => 0.00148,
                'is_active' => true
            ],
            [
                'from_currency_id' => $btc->id,
                'to_currency_id' => $ltc->id,
                'rate' => 669.23, // 1 BTC = 669.23 LTC
                'buy_rate' => 669.23,
                'sell_rate' => 667.50,
                'is_active' => true
            ],
            [
                'from_currency_id' => $ltc->id,
                'to_currency_id' => $usdt->id,
                'rate' => 100, // 1 LTC = 100 USDT
                'buy_rate' => 100,
                'sell_rate' => 99.5,
                'is_active' => true
            ],
            [
                'from_currency_id' => $usdt->id,
                'to_currency_id' => $ltc->id,
                'rate' => 0.01, // 1 USDT = 0.01 LTC
                'buy_rate' => 0.01,
                'sell_rate' => 0.00995,
                'is_active' => true
            ],

            // Some inactive exchange rates for testing
            [
                'from_currency_id' => $ada->id,
                'to_currency_id' => $dot->id,
                'rate' => 0.078, // 1 ADA = 0.078 DOT
                'buy_rate' => 0.078,
                'sell_rate' => 0.077,
                'is_active' => false
            ],
            [
                'from_currency_id' => $dot->id,
                'to_currency_id' => $ada->id,
                'rate' => 12.78, // 1 DOT = 12.78 ADA
                'buy_rate' => 12.78,
                'sell_rate' => 12.70,
                'is_active' => false
            ]
        ];

        foreach ($exchangeRates as $rate) {
            ExchangeRate::create($rate);
        }
    }
}