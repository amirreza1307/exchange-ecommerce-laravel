<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'currency_id' => Currency::factory(),
            'type' => $this->faker->randomElement(['deposit', 'withdraw', 'buy', 'sell']),
            'amount' => $this->faker->randomFloat(8, 1, 1000),
            'fee' => $this->faker->randomFloat(8, 0, 10),
            'final_amount' => function (array $attributes) {
                return $attributes['amount'] - $attributes['fee'];
            },
            'status' => $this->faker->randomElement(['pending', 'completed', 'failed']),
            'reference_id' => $this->faker->uuid(),
            'description' => $this->faker->sentence(),
            'processed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function deposit(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'deposit',
        ]);
    }

    public function withdrawal(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'withdraw',
        ]);
    }

    public function buy(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'buy',
        ]);
    }

    public function sell(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'sell',
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }
}
