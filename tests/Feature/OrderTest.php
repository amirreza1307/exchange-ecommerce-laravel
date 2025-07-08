<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Currency;
use App\Models\Order;
use App\Models\Wallet;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $btc;
    protected $usdt;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        
        $this->user = User::where('role', 'user')->first();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
        $this->btc = Currency::where('symbol', 'BTC')->first();
        $this->usdt = Currency::where('symbol', 'USDT')->first();
    }

    public function test_user_can_get_price_quote_for_buy(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/orders/quote', [
            'type' => 'buy',
            'to_currency_id' => $this->btc->id,
            'amount' => 0.001
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'type',
                        'currency',
                        'amount',
                        'unit_price',
                        'total_cost',
                        'commission_rate',
                        'commission_amount',
                        'final_cost'
                    ]
                ]);

        $quote = $response->json('data');
        $this->assertEquals('buy', $quote['type']);
        $this->assertEquals(0.001, $quote['amount']);
    }

    public function test_user_can_get_price_quote_for_sell(): void
    {
        // First give user some BTC
        $wallet = $this->user->getOrCreateWallet($this->btc->id);
        $wallet->addBalance(0.01);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/orders/quote', [
            'type' => 'sell',
            'from_currency_id' => $this->btc->id,
            'to_currency_id' => 1, // IRR currency ID
            'amount' => 0.001
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'type',
                        'currency',
                        'amount',
                        'unit_price',
                        'total_value',
                        'commission_rate',
                        'commission_amount',
                        'final_value'
                    ]
                ]);

        $quote = $response->json('data');
        $this->assertEquals('sell', $quote['type']);
        $this->assertEquals(0.001, $quote['amount']);
    }

    public function test_user_can_create_buy_order(): void
    {
        $amount = 0.001;
        $expectedCost = $amount * $this->btc->buy_price;
        $expectedCommission = ($expectedCost * $this->btc->buy_commission) / 100;
        $expectedFinalCost = $expectedCost + $expectedCommission;

        // Ensure user has enough rial balance
        $this->user->update(['rial_balance' => $expectedFinalCost + 1000000]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/orders/buy', [
            'currency_id' => $this->btc->id,
            'amount' => $amount
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'order_number',
                        'type',
                        'status',
                        'to_amount',
                        'final_amount'
                    ]
                ]);

        $order = $response->json('data');
        $this->assertEquals('buy', $order['type']);
        $this->assertEquals('completed', $order['status']);

        // Check if order was created in database
        $this->assertDatabaseHas('orders', [
            'user_id' => $this->user->id,
            'type' => 'buy',
            'to_currency_id' => $this->btc->id,
            'to_amount' => $amount
        ]);

        // Check if user wallet was updated
        $wallet = $this->user->getWalletForCurrency($this->btc->id);
        $this->assertEquals($amount, $wallet->balance);
    }

    public function test_user_cannot_buy_with_insufficient_balance(): void
    {
        // Set user balance to very low amount
        $this->user->update(['rial_balance' => 1000]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/orders/buy', [
            'currency_id' => $this->btc->id,
            'amount' => 0.001
        ]);

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Insufficient rial balance'
                ]);
    }

    public function test_user_can_create_sell_order(): void
    {
        $amount = 0.001;

        // Give user some BTC first
        $wallet = $this->user->getOrCreateWallet($this->btc->id);
        $wallet->addBalance($amount);

        $originalRialBalance = $this->user->rial_balance;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/orders/sell', [
            'currency_id' => $this->btc->id,
            'amount' => $amount
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'order_number',
                        'type',
                        'status',
                        'from_amount',
                        'final_amount'
                    ]
                ]);

        $order = $response->json('data');
        $this->assertEquals('sell', $order['type']);
        $this->assertEquals('completed', $order['status']);

        // Check if order was created in database
        $this->assertDatabaseHas('orders', [
            'user_id' => $this->user->id,
            'type' => 'sell',
            'from_currency_id' => $this->btc->id,
            'from_amount' => $amount
        ]);

        // Check if user's BTC was deducted
        $wallet->refresh();
        $this->assertEquals(0, $wallet->balance);

        // Check if user's rial balance increased
        $this->user->refresh();
        $this->assertGreaterThan($originalRialBalance, $this->user->rial_balance);
    }

    public function test_user_cannot_sell_with_insufficient_balance(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/orders/sell', [
            'currency_id' => $this->btc->id,
            'amount' => 0.001
        ]);

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Insufficient currency balance'
                ]);
    }

    public function test_user_can_exchange_currencies(): void
    {
        $btcAmount = 0.001;
        
        // Give user some BTC first
        $btcWallet = $this->user->getOrCreateWallet($this->btc->id);
        $btcWallet->addBalance($btcAmount);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/orders/exchange', [
            'from_currency_id' => $this->btc->id,
            'to_currency_id' => $this->usdt->id,
            'amount' => $btcAmount
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'order_number',
                        'type',
                        'status',
                        'from_amount',
                        'to_amount'
                    ]
                ]);

        $order = $response->json('data');
        $this->assertEquals('exchange', $order['type']);
        $this->assertEquals('completed', $order['status']);

        // Check if BTC was deducted
        $btcWallet->refresh();
        $this->assertEquals(0, $btcWallet->balance);

        // Check if USDT was added
        $usdtWallet = $this->user->getWalletForCurrency($this->usdt->id);
        $this->assertGreaterThan(0, $usdtWallet->balance);
    }

    public function test_user_can_view_orders(): void
    {
        // Create some test orders
        Order::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/orders');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'data' => [
                            '*' => [
                                'id',
                                'order_number',
                                'type',
                                'status',
                                'created_at'
                            ]
                        ]
                    ]
                ]);
    }

    public function test_user_can_view_specific_order(): void
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/orders/{$order->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'order_number',
                        'type',
                        'status',
                        'from_currency',
                        'to_currency'
                    ]
                ]);
    }

    public function test_user_cannot_view_other_users_orders(): void
    {
        $otherUser = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/orders/{$order->id}");

        $response->assertStatus(404);
    }

    public function test_user_can_cancel_pending_order(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson("/api/orders/{$order->id}/cancel", [
            'reason' => 'Changed my mind'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Order cancelled successfully'
                ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled',
            'cancellation_reason' => 'Changed my mind'
        ]);
    }

    public function test_user_cannot_cancel_completed_order(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'completed'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Order cannot be cancelled'
                ]);
    }

    public function test_order_validation_rules(): void
    {
        // Test invalid currency_id
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/orders/buy', [
            'currency_id' => 999,
            'amount' => 0.001
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['currency_id']);

        // Test invalid amount
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/orders/buy', [
            'currency_id' => $this->btc->id,
            'amount' => -0.001
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['amount']);

        // Test missing required fields
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/orders/buy', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['currency_id', 'amount']);
    }
}
