<?php

namespace Database\Factories;

use App\Models\ExchangeRate;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExchangeRateFactory extends Factory
{
    protected $model = ExchangeRate::class;

    public function definition(): array
    {
        $fromCurrency = Currency::factory();
        $toCurrency = Currency::factory();
        $rate = $this->faker->randomFloat(8, 100, 100000);
        
        return [
            'from_currency_id' => $fromCurrency,
            'to_currency_id' => $toCurrency,
            'rate' => $rate,
            'buy_rate' => $rate * 1.01, // 1% higher than rate
            'sell_rate' => $rate * 0.99, // 1% lower than rate
            'is_active' => true,
            'last_updated' => now(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
