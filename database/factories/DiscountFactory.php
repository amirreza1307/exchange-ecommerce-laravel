<?php

namespace Database\Factories;

use App\Models\Discount;
use Illuminate\Database\Eloquent\Factories\Factory;

class DiscountFactory extends Factory
{
    protected $model = Discount::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->bothify('DISC###??'),
            'title' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'type' => $this->faker->randomElement(['percentage', 'fixed']),
            'value' => $this->faker->randomFloat(2, 1, 100),
            'min_order_amount' => $this->faker->randomFloat(2, 0, 1000),
            'usage_limit' => $this->faker->numberBetween(1, 100),
            'used_count' => 0,
            'is_active' => true,
            'expires_at' => $this->faker->dateTimeBetween('now', '+1 year'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function percentage(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'percentage',
            'value' => $this->faker->randomFloat(2, 1, 50),
        ]);
    }

    public function fixed(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'fixed',
            'value' => $this->faker->randomFloat(2, 1000, 10000),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => $this->faker->dateTimeBetween('-1 year', '-1 day'),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
