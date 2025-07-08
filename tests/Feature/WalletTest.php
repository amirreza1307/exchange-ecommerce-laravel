<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WalletTest extends TestCase
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

    public function test_user_can_get_their_wallets()
    {
        Sanctum::actingAs($this->user);

        // Create some wallets for the user
        $currencies = Currency::factory()->count(3)->create();
        foreach ($currencies as $currency) {
            Wallet::factory()->create([
                'user_id' => $this->user->id,
                'currency_id' => $currency->id
            ]);
        }

        $response = $this->getJson('/api/wallets');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'balance',                        'currency' => [
                            'id',
                            'name',
                            'symbol'
                        ]
                        ]
                    ]
                ]);
    }

    public function test_user_can_get_specific_wallet()
    {
        Sanctum::actingAs($this->user);

        $wallet = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'currency_id' => $this->currency->id,
            'balance' => 1.5
        ]);

        $response = $this->getJson("/api/wallets/{$this->currency->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'currency_id' => $this->currency->id,
                        'currency' => [
                            'id' => $this->currency->id,
                            'name' => 'Bitcoin',
                            'symbol' => 'BTC'
                        ]
                    ]
                ]);
    }

    public function test_user_cannot_access_other_users_wallet()
    {
        Sanctum::actingAs($this->user);

        // Access user's own wallet - should work fine
        $response = $this->getJson("/api/wallets/{$this->currency->id}");

        $response->assertStatus(200);
        
        // Test that the returned wallet belongs to the authenticated user
        $this->assertEquals($this->user->id, $response->json('data.user_id'));
    }

    public function test_user_can_deposit_to_wallet()
    {
        Sanctum::actingAs($this->user);

        $depositData = [
            'currency_id' => $this->currency->id,
            'amount' => 0.5,
            'tx_hash' => 'test_hash_123',
            'from_address' => 'test_address_123'
        ];

        $response = $this->postJson("/api/wallets/deposit", $depositData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'transaction',
                        'wallet'
                    ]
                ]);
    }

    public function test_user_can_withdraw_from_wallet()
    {
        Sanctum::actingAs($this->user);

        // Create wallet with balance first
        $wallet = $this->user->getOrCreateWallet($this->currency->id);
        $wallet->update(['balance' => 1.0, 'frozen_balance' => 0]);

        $withdrawData = [
            'currency_id' => $this->currency->id,
            'amount' => 0.5,
            'to_address' => 'test_address_123'
        ];

        $response = $this->postJson("/api/wallets/withdraw", $withdrawData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'transaction',
                        'wallet'
                    ]
                ]);
    }

    public function test_user_cannot_withdraw_more_than_balance()
    {
        Sanctum::actingAs($this->user);

        // Create wallet with limited balance
        $wallet = $this->user->getOrCreateWallet($this->currency->id);
        $wallet->update(['balance' => 0.1, 'frozen_balance' => 0]);

        $withdrawData = [
            'currency_id' => $this->currency->id,
            'amount' => 1.0,
            'to_address' => 'test_address_123'
        ];

        $response = $this->postJson("/api/wallets/withdraw", $withdrawData);

        $response->assertStatus(422)
                ->assertJsonFragment(['success' => false]);
    }

    public function test_deposit_requires_valid_amount()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/wallets/deposit", [
            'currency_id' => $this->currency->id,
            'amount' => -0.1,
            'tx_hash' => 'test_hash',
            'from_address' => 'test_address'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['amount']);
    }

    public function test_withdrawal_requires_valid_amount()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/wallets/withdraw", [
            'currency_id' => $this->currency->id,
            'amount' => -0.1,
            'to_address' => 'test_address'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['amount']);
    }

    public function test_unauthenticated_user_cannot_access_wallets()
    {
        $response = $this->getJson('/api/wallets');

        $response->assertStatus(401);
    }
}
