<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CurrencySeeder::class,
            AdminSeeder::class,
            WalletSeeder::class,
            OrderSeeder::class,
            TransactionSeeder::class,
            DiscountSeeder::class,
            ExchangeRateSeeder::class,
        ]);
    }
}
