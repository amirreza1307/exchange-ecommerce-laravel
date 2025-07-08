<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Currency;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::factory()->create(['role' => 'user']);
        $btc = Currency::where('symbol', 'BTC')->first() ?? Currency::factory()->create(['symbol' => 'BTC']);
        $irr = Currency::where('symbol', 'IRR')->first() ?? Currency::factory()->create(['symbol' => 'IRR']);
        
        return [
            'order_number' => 'ORD-' . now()->format('YmdHis') . '-' . $this->faker->randomNumber(4),
            'user_id' => $user->id,
            'type' => $this->faker->randomElement(['buy', 'sell', 'exchange']),
            'from_currency_id' => $irr->id,
            'to_currency_id' => $btc->id,
            'from_amount' => $this->faker->randomFloat(8, 0.001, 1),
            'to_amount' => $this->faker->randomFloat(8, 0.001, 1),
            'exchange_rate' => $this->faker->randomFloat(8, 1000000, 5000000000),
            'commission_rate' => $this->faker->randomFloat(2, 0.1, 1),
            'commission_amount' => $this->faker->randomFloat(8, 1000, 100000),
            'final_amount' => $this->faker->randomFloat(8, 0.001, 1),
            'status' => $this->faker->randomElement(['pending', 'processing', 'completed', 'cancelled']),
            'discount_amount' => 0,
            'processed_at' => $this->faker->optional()->dateTime(),
        ];
    }

    public function pending()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'processed_at' => null,
        ]);
    }

    public function completed()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'processed_at' => now(),
        ]);
    }

    public function cancelled()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancellation_reason' => $this->faker->sentence(),
        ]);
    }
}
