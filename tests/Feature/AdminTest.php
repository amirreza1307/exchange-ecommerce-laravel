<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Discount;
use App\Models\ExchangeRate;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->user = User::factory()->create(['role' => 'user']);
    }

    public function test_admin_can_get_dashboard_stats()
    {
        Sanctum::actingAs($this->admin);

        // Create some test data
        User::factory()->count(5)->create();
        Order::factory()->count(10)->create();
        Transaction::factory()->count(15)->create();

        $response = $this->getJson('/api/admin/dashboard');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'stats' => [
                            'total_users',
                            'total_orders',
                            'total_transactions'
                        ],
                        'revenue',
                        'recent_orders',
                        'recent_users'
                    ]
                ]);
    }

    public function test_admin_can_get_all_users()
    {
        Sanctum::actingAs($this->admin);

        User::factory()->count(5)->create();

        $response = $this->getJson('/api/admin/users');

        $response->assertStatus(200);
        
        // Check that response has paginated structure
        $responseData = $response->json();
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('data', $responseData['data']);
        $this->assertIsArray($responseData['data']['data']);
    }

    public function test_admin_can_search_users()
    {
        Sanctum::actingAs($this->admin);

        User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);

        $response = $this->getJson('/api/admin/users?search=john');

        $response->assertStatus(200);
        
        $users = $response->json('data.data'); // Get paginated data
        $this->assertGreaterThan(0, count($users));
        foreach ($users as $user) {
            $this->assertTrue(
                str_contains(strtolower($user['name']), 'john') || 
                str_contains(strtolower($user['email']), 'john')
            );
        }
    }

    public function test_admin_can_update_user_status()
    {
        Sanctum::actingAs($this->admin);

        $user = User::factory()->create(['is_active' => true]);

        $response = $this->putJson("/api/admin/users/{$user->id}", [
            'is_active' => false
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'id' => $user->id,
                        'is_active' => false
                    ]
                ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => false
        ]);
    }

    public function test_admin_can_get_all_orders()
    {
        Sanctum::actingAs($this->admin);

        $currency = Currency::factory()->create();
        Order::factory()->count(5)->create(['from_currency_id' => $currency->id]);

        $response = $this->getJson('/api/admin/orders');

        $response->assertStatus(200);
        
        // Check paginated response structure
        $responseData = $response->json();
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('data', $responseData['data']);
        $this->assertIsArray($responseData['data']['data']);
    }

    public function test_admin_can_filter_orders_by_status()
    {
        Sanctum::actingAs($this->admin);

        $currency = Currency::factory()->create();
        
        Order::factory()->create([
            'from_currency_id' => $currency->id,
            'status' => 'pending'
        ]);
        
        Order::factory()->create([
            'from_currency_id' => $currency->id,
            'status' => 'completed'
        ]);

        $response = $this->getJson('/api/admin/orders?status=pending');

        $response->assertStatus(200);
        
        $orders = $response->json('data.data'); // Get paginated data
        foreach ($orders as $order) {
            $this->assertEquals('pending', $order['status']);
        }
    }

    public function test_admin_can_update_order_status()
    {
        Sanctum::actingAs($this->admin);

        $currency = Currency::factory()->create();
        $order = Order::factory()->create([
            'from_currency_id' => $currency->id,
            'status' => 'pending'
        ]);

        $response = $this->putJson("/api/admin/orders/{$order->id}", [
            'status' => 'completed'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'id' => $order->id,
                        'status' => 'completed'
                    ]
                ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'completed'
        ]);
    }

    public function test_admin_can_create_exchange_rate()
    {
        Sanctum::actingAs($this->admin);

        $fromCurrency = Currency::factory()->create(['symbol' => 'USD']);
        $toCurrency = Currency::factory()->create(['symbol' => 'BTC']);

        $exchangeRateData = [
            'from_currency_id' => $fromCurrency->id,
            'to_currency_id' => $toCurrency->id,
            'rate' => 50000,
            'buy_rate' => 50000,
            'sell_rate' => 49000,
            'is_active' => true
        ];

        $response = $this->postJson('/api/admin/exchange-rates', $exchangeRateData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'rate',
                        'buy_rate',
                        'sell_rate',
                        'is_active',
                        'from_currency',
                        'to_currency'
                    ]
                ]);

        $this->assertDatabaseHas('exchange_rates', [
            'from_currency_id' => $fromCurrency->id,
            'to_currency_id' => $toCurrency->id,
            'rate' => 50000,
            'buy_rate' => 50000,
            'sell_rate' => 49000
        ]);
    }

    public function test_admin_can_update_exchange_rate()
    {
        Sanctum::actingAs($this->admin);

        $fromCurrency = Currency::factory()->create(['symbol' => 'USD']);
        $toCurrency = Currency::factory()->create(['symbol' => 'BTC']);
        $exchangeRate = ExchangeRate::factory()->create([
            'from_currency_id' => $fromCurrency->id,
            'to_currency_id' => $toCurrency->id,
            'rate' => 50000,
            'buy_rate' => 50000,
            'sell_rate' => 49000
        ]);

        $response = $this->putJson("/api/admin/exchange-rates/{$exchangeRate->id}", [
            'buy_rate' => 55000,
            'sell_rate' => 54000
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'id' => $exchangeRate->id,
                        'buy_rate' => '55000.00000000',
                        'sell_rate' => '54000.00000000'
                    ]
                ]);
    }

    public function test_admin_can_create_discount()
    {
        Sanctum::actingAs($this->admin);

        $discountData = [
            'code' => 'SAVE20',
            'title' => 'Save 20%',
            'type' => 'percentage',
            'value' => 20,
            'min_order_amount' => 10000,
            'usage_limit' => 100,
            'expires_at' => now()->addMonth()->format('Y-m-d H:i:s')
        ];

        $response = $this->postJson('/api/admin/discounts', $discountData);

        $response->assertStatus(201);
        
        $responseData = $response->json('data');
        $this->assertEquals('SAVE20', $responseData['code']);
        $this->assertEquals('Save 20%', $responseData['title']);
        $this->assertEquals('percentage', $responseData['type']);
        $this->assertEquals(20, $responseData['value']);

        $this->assertDatabaseHas('discounts', [
            'code' => 'SAVE20',
            'type' => 'percentage',
            'value' => 20
        ]);
    }

    public function test_admin_can_update_discount()
    {
        Sanctum::actingAs($this->admin);

        $discount = Discount::factory()->create([
            'code' => 'SAVE10',
            'value' => 10,
            'is_active' => true
        ]);

        $response = $this->putJson("/api/admin/discounts/{$discount->id}", [
            'value' => 15,
            'is_active' => false
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'id' => $discount->id,
                        'value' => 15,
                        'is_active' => false
                    ]
                ]);
    }

    public function test_admin_can_delete_discount()
    {
        Sanctum::actingAs($this->admin);

        $discount = Discount::factory()->create();

        $response = $this->deleteJson("/api/admin/discounts/{$discount->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Discount deleted successfully'
                ]);

        $this->assertDatabaseMissing('discounts', [
            'id' => $discount->id
        ]);
    }

    public function test_regular_user_cannot_access_admin_endpoints()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/admin/dashboard');
        $response->assertStatus(403);

        $response = $this->getJson('/api/admin/users');
        $response->assertStatus(403);

        $response = $this->getJson('/api/admin/orders');
        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_admin_endpoints()
    {
        $response = $this->getJson('/api/admin/dashboard');
        $response->assertStatus(401);

        $response = $this->getJson('/api/admin/users');
        $response->assertStatus(401);

        $response = $this->getJson('/api/admin/orders');
        $response->assertStatus(401);
    }

    public function test_admin_exchange_rate_creation_requires_valid_data()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/admin/exchange-rates', [
            'buy_rate' => -100,
            'sell_rate' => 'invalid'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['from_currency_id', 'to_currency_id', 'rate']);
    }

    public function test_admin_discount_creation_requires_valid_data()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/admin/discounts', [
            'type' => 'invalid_type',
            'value' => -10
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['code', 'type', 'value']);
    }
}
