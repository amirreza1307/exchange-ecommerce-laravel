<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin',
            'email' => 'admin@exchange.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
            'rial_balance' => 1000000000, // 1 billion IRR for testing
        ]);

        // Create test user
        User::create([
            'name' => 'Test User',
            'email' => 'user@exchange.com',
            'password' => Hash::make('password'),
            'phone' => '09123456789',
            'national_id' => '1234567890',
            'bank_account' => '123-456-789',
            'bank_name' => 'Bank Melli',
            'role' => 'user',
            'is_active' => true,
            'rial_balance' => 10000000, // 10 million IRR for testing
        ]);
    }
}
