<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Currency;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guest_can_view_currencies(): void
    {
        $response = $this->getJson('/api/currencies');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'data' => [
                            '*' => [
                                'id',
                                'symbol',
                                'name',
                                'buy_price',
                                'sell_price'
                            ]
                        ]
                    ]
                ]);
    }

    public function test_guest_can_view_trading_currencies(): void
    {
        $response = $this->getJson('/api/currencies/trading');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'symbol',
                            'name',
                            'is_tradeable'
                        ]
                    ]
                ]);

        // All returned currencies should be tradeable
        $currencies = $response->json('data');
        foreach ($currencies as $currency) {
            $this->assertTrue($currency['is_tradeable']);
        }
    }

    public function test_admin_can_create_currency(): void
    {
        $admin = User::where('role', 'admin')->first();
        $token = $admin->createToken('test-token')->plainTextToken;

        $currencyData = [
            'symbol' => 'XRP',
            'name' => 'Ripple',
            'description' => 'XRP - Digital payment protocol',
            'buy_price' => 35000,
            'sell_price' => 34500,
            'buy_commission' => 0.3,
            'sell_commission' => 0.3,
            'treasury_balance' => 10000,
            'decimal_places' => 6,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/admin/currencies', $currencyData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'symbol',
                        'name',
                        'buy_price',
                        'sell_price'
                    ]
                ]);

        $this->assertDatabaseHas('currencies', [
            'symbol' => 'XRP',
            'name' => 'Ripple'
        ]);
    }

    public function test_regular_user_cannot_create_currency(): void
    {
        $user = User::where('role', 'user')->first();
        $token = $user->createToken('test-token')->plainTextToken;

        $currencyData = [
            'symbol' => 'XRP',
            'name' => 'Ripple',
            'buy_price' => 35000,
            'sell_price' => 34500,
            'buy_commission' => 0.3,
            'sell_commission' => 0.3,
            'treasury_balance' => 10000,
            'decimal_places' => 6,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/admin/currencies', $currencyData);

        $response->assertStatus(403);
    }

    public function test_admin_can_update_currency(): void
    {
        $admin = User::where('role', 'admin')->first();
        $token = $admin->createToken('test-token')->plainTextToken;

        $currency = Currency::where('symbol', 'BTC')->first();

        $updateData = [
            'buy_price' => 4400000000,
            'sell_price' => 4380000000,
            'is_active' => false
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/admin/currencies/{$currency->id}", $updateData);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Currency updated successfully'
                ]);

        $this->assertDatabaseHas('currencies', [
            'id' => $currency->id,
            'buy_price' => 4400000000,
            'is_active' => false
        ]);
    }

    public function test_admin_can_update_treasury_balance(): void
    {
        $admin = User::where('role', 'admin')->first();
        $token = $admin->createToken('test-token')->plainTextToken;

        $currency = Currency::where('symbol', 'BTC')->first();
        $originalBalance = $currency->treasury_balance;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/admin/currencies/{$currency->id}/treasury", [
            'amount' => 5,
            'operation' => 'add'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Treasury balance updated successfully'
                ]);

        $currency->refresh();
        $this->assertEquals($originalBalance + 5, $currency->treasury_balance);
    }

    public function test_treasury_balance_cannot_go_negative(): void
    {
        $admin = User::where('role', 'admin')->first();
        $token = $admin->createToken('test-token')->plainTextToken;

        $currency = Currency::where('symbol', 'BTC')->first();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/admin/currencies/{$currency->id}/treasury", [
            'amount' => 999999,
            'operation' => 'subtract'
        ]);

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Insufficient treasury balance'
                ]);
    }

    public function test_currency_validation_rules(): void
    {
        $admin = User::where('role', 'admin')->first();
        $token = $admin->createToken('test-token')->plainTextToken;

        // Test duplicate symbol
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/admin/currencies', [
            'symbol' => 'BTC', // Already exists
            'name' => 'Bitcoin Copy',
            'buy_price' => 100000,
            'sell_price' => 99000,
            'buy_commission' => 0.5,
            'sell_commission' => 0.5,
            'treasury_balance' => 10,
            'decimal_places' => 8,
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['symbol']);

        // Test missing required fields
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/admin/currencies', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'symbol',
                    'name',
                    'buy_price',
                    'sell_price',
                    'buy_commission',
                    'sell_commission',
                    'treasury_balance',
                    'decimal_places'
                ]);
    }

    public function test_currency_search_functionality(): void
    {
        $response = $this->getJson('/api/currencies?search=BTC');

        $response->assertStatus(200);
        
        $currencies = $response->json('data.data');
        $this->assertNotEmpty($currencies);
        
        // Check if all returned currencies contain 'BTC' in symbol or name
        foreach ($currencies as $currency) {
            $this->assertTrue(
                stripos($currency['symbol'], 'BTC') !== false || 
                stripos($currency['name'], 'BTC') !== false
            );
        }
    }

    public function test_currency_filter_by_active_status(): void
    {
        // Test active currencies
        $response = $this->getJson('/api/currencies?active=1');
        $response->assertStatus(200);
        
        $currencies = $response->json('data.data');
        foreach ($currencies as $currency) {
            $this->assertTrue($currency['is_active']);
        }

        // Test inactive currencies
        $response = $this->getJson('/api/currencies?active=0');
        $response->assertStatus(200);
        
        $currencies = $response->json('data.data');
        foreach ($currencies as $currency) {
            $this->assertFalse($currency['is_active']);
        }
    }
}
