<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

class WalletFactory extends Factory
{
    protected $model = Wallet::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'currency_id' => Currency::factory(),
            'balance' => $this->faker->randomFloat(8, 0, 1000),
            'frozen_balance' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
