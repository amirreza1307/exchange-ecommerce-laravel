<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->currency = Currency::factory()->create([
            'name' => 'Bitcoin',
            'symbol' => 'BTC',
            'is_active' => true
        ]);
    }

    public function test_user_can_get_their_transactions()
    {
        Sanctum::actingAs($this->user);

        // Create some transactions for the user
        Transaction::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'currency_id' => $this->currency->id
        ]);

        $response = $this->getJson('/api/transactions');

        $response->assertStatus(200);
        
        // Check paginated response structure
        $responseData = $response->json();
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('data', $responseData['data']);
        $this->assertIsArray($responseData['data']['data']);
    }

    public function test_user_can_filter_transactions_by_type()
    {
        Sanctum::actingAs($this->user);

        // Create different types of transactions
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'currency_id' => $this->currency->id,
            'type' => 'buy'
        ]);

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'currency_id' => $this->currency->id,
            'type' => 'sell'
        ]);

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'currency_id' => $this->currency->id,
            'type' => 'deposit'
        ]);

        $response = $this->getJson('/api/transactions?type=buy');

        $response->assertStatus(200);
        
        $transactions = $response->json('data.data'); // Get paginated data
        foreach ($transactions as $transaction) {
            $this->assertEquals('buy', $transaction['type']);
        }
    }

    public function test_user_can_filter_transactions_by_status()
    {
        Sanctum::actingAs($this->user);

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'currency_id' => $this->currency->id,
            'status' => 'completed'
        ]);

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'currency_id' => $this->currency->id,
            'status' => 'pending'
        ]);

        $response = $this->getJson('/api/transactions?status=completed');

        $response->assertStatus(200);
        
        $transactions = $response->json('data.data'); // Get paginated data
        foreach ($transactions as $transaction) {
            $this->assertEquals('completed', $transaction['status']);
        }
    }

    public function test_user_can_filter_transactions_by_currency()
    {
        Sanctum::actingAs($this->user);

        $currency2 = Currency::factory()->create([
            'name' => 'Ethereum',
            'symbol' => 'ETH'
        ]);

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'currency_id' => $this->currency->id,
        ]);

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'currency_id' => $currency2->id,
        ]);

        $response = $this->getJson("/api/transactions?currency_id={$this->currency->id}");

        $response->assertStatus(200);
        
        $transactions = $response->json('data.data'); // Get paginated data
        foreach ($transactions as $transaction) {
            $this->assertEquals($this->currency->id, $transaction['currency']['id']);
        }
    }

    public function test_user_can_get_specific_transaction()
    {
        Sanctum::actingAs($this->user);

        $transaction = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'currency_id' => $this->currency->id,
            'type' => 'buy',
            'amount' => 0.5,
            'fee' => 5,
            'final_amount' => 24995,
            'status' => 'completed'
        ]);

        $response = $this->getJson("/api/transactions/{$transaction->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'id' => $transaction->id,
                        'type' => 'buy',
                        'amount' => '0.50000000',
                        'fee' => '5.00000000',
                        'final_amount' => '24995.00000000',
                        'status' => 'completed'
                    ]
                ]);
    }

    public function test_user_cannot_access_other_users_transaction()
    {
        $otherUser = User::factory()->create();
        $transaction = Transaction::factory()->create([
            'user_id' => $otherUser->id,
            'currency_id' => $this->currency->id
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/transactions/{$transaction->id}");

        $response->assertStatus(404);
    }

    public function test_user_can_create_deposit_transaction()
    {
        Sanctum::actingAs($this->user);

        $transactionData = [
            'currency_id' => $this->currency->id,
            'amount' => 0.5,
            'tx_hash' => 'test_hash_123',
            'from_address' => 'test_address_123'
        ];

        $response = $this->postJson('/api/wallets/deposit', $transactionData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'transaction',
                        'wallet'
                    ]
                ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'type' => 'deposit',
            'amount' => 0.5
        ]);
    }

    public function test_transaction_creation_requires_valid_data()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/wallets/deposit', [
            'amount' => -100
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['currency_id', 'amount', 'tx_hash', 'from_address']);
    }

    public function test_unauthenticated_user_cannot_access_transactions()
    {
        $response = $this->getJson('/api/transactions');

        $response->assertStatus(401);
    }

    public function test_user_can_filter_transactions_by_date_range()
    {
        Sanctum::actingAs($this->user);

        // Create transaction from yesterday
        $yesterday = now()->subDay();
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'currency_id' => $this->currency->id,
            'created_at' => $yesterday
        ]);

        // Create transaction from today
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'currency_id' => $this->currency->id,
            'created_at' => now()
        ]);

        $response = $this->getJson('/api/transactions?from=' . $yesterday->format('Y-m-d') . '&to=' . now()->format('Y-m-d'));

        $response->assertStatus(200);
        
        $this->assertCount(2, $response->json('data.data'));
    }
}
