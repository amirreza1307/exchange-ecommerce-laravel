<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\User;

class TestEndpoints extends Command
{
    protected $signature = 'test:endpoints';
    protected $description = 'Test all API endpoints';

    public function handle()
    {
        $this->info('Testing API Endpoints...');
        
        $baseUrl = 'http://localhost:8000/api';
        
        // Test public endpoints
        $this->testPublicEndpoints($baseUrl);
        
        // Test authenticated endpoints
        $this->testAuthenticatedEndpoints($baseUrl);
        
        // Test admin endpoints
        $this->testAdminEndpoints($baseUrl);
        
        $this->info('All endpoint tests completed!');
    }
    
    private function testPublicEndpoints($baseUrl)
    {
        $this->info("\n--- Testing Public Endpoints ---");
        
        // Test currencies endpoint
        $this->testEndpoint('GET', $baseUrl . '/currencies', 'Get all currencies');
        $this->testEndpoint('GET', $baseUrl . '/currencies/trading', 'Get trading currencies');
        $this->testEndpoint('GET', $baseUrl . '/currencies/1', 'Get currency detail');
        
        // Test auth endpoints
        $this->testEndpoint('POST', $baseUrl . '/auth/register', 'Register user', [
            'name' => 'Test User ' . time(),
            'email' => 'test' . time() . '@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ]);
        
        $this->testEndpoint('POST', $baseUrl . '/auth/login', 'User login', [
            'email' => 'user@exchange.com',
            'password' => 'password'
        ]);
        
        $this->testEndpoint('POST', $baseUrl . '/admin/auth/login', 'Admin login', [
            'email' => 'admin@exchange.com',
            'password' => 'password'
        ]);
    }
    
    private function testAuthenticatedEndpoints($baseUrl)
    {
        $this->info("\n--- Testing Authenticated Endpoints ---");
        
        // Get user token
        $user = User::where('email', 'user@exchange.com')->first();
        if (!$user) {
            $this->error('Test user not found!');
            return;
        }
        
        $token = $user->createToken('test')->plainTextToken;
        
        // Test wallet endpoints
        $this->testEndpoint('GET', $baseUrl . '/wallets', 'Get wallets', null, $token);
        $this->testEndpoint('GET', $baseUrl . '/wallets/portfolio', 'Get portfolio', null, $token);
        $this->testEndpoint('GET', $baseUrl . '/wallets/1', 'Get specific wallet', null, $token);
        
        // Test order endpoints
        $this->testEndpoint('GET', $baseUrl . '/orders', 'Get orders', null, $token);
        $this->testEndpoint('POST', $baseUrl . '/orders/quote', 'Get price quote', [
            'type' => 'buy',
            'to_currency_id' => 2,
            'amount' => 0.001
        ], $token);
        
        // Test transaction endpoints
        $this->testEndpoint('GET', $baseUrl . '/transactions', 'Get transactions', null, $token);
        
        // Test profile endpoints
        $this->testEndpoint('GET', $baseUrl . '/auth/profile', 'Get profile', null, $token);
        
        // Clean up token
        $user->tokens()->where('name', 'test')->delete();
    }
    
    private function testAdminEndpoints($baseUrl)
    {
        $this->info("\n--- Testing Admin Endpoints ---");
        
        // Get admin token
        $admin = User::where('email', 'admin@exchange.com')->first();
        if (!$admin) {
            $this->error('Admin user not found!');
            return;
        }
        
        $token = $admin->createToken('test')->plainTextToken;
        
        // Test admin endpoints
        $this->testEndpoint('GET', $baseUrl . '/admin/dashboard', 'Admin dashboard', null, $token);
        $this->testEndpoint('GET', $baseUrl . '/admin/users', 'Admin get users', null, $token);
        $this->testEndpoint('GET', $baseUrl . '/admin/orders', 'Admin get orders', null, $token);
        $this->testEndpoint('GET', $baseUrl . '/admin/transactions', 'Admin get transactions', null, $token);
        
        // Clean up token
        $admin->tokens()->where('name', 'test')->delete();
    }
    
    private function testEndpoint($method, $url, $description, $data = null, $token = null)
    {
        try {
            $request = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => $token ? 'Bearer ' . $token : null,
            ]);
            
            if ($method === 'GET') {
                $response = $request->get($url);
            } else {
                $response = $request->$method($url, $data ?: []);
            }
            
            if ($response->successful()) {
                $this->info("✓ $description - Status: {$response->status()}");
            } else {
                $this->error("✗ $description - Status: {$response->status()}");
                if ($response->status() !== 404) {
                    $this->error("  Response: " . $response->body());
                }
            }
        } catch (\Exception $e) {
            $this->error("✗ $description - Error: " . $e->getMessage());
        }
    }
}