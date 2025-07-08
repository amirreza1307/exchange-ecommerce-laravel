<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Currency>
 */
class CurrencyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'symbol' => strtoupper($this->faker->unique()->lexify('???')),
            'name' => $this->faker->company(),
            'description' => $this->faker->sentence(),
            'buy_price' => $this->faker->randomFloat(8, 1000, 5000000000),
            'sell_price' => $this->faker->randomFloat(8, 1000, 5000000000),
            'buy_commission' => $this->faker->randomFloat(2, 0.1, 2),
            'sell_commission' => $this->faker->randomFloat(2, 0.1, 2),
            'treasury_balance' => $this->faker->randomFloat(8, 10, 10000),
            'decimal_places' => $this->faker->randomElement([6, 8]),
            'is_active' => true,
            'is_tradeable' => true,
        ];
    }
}
