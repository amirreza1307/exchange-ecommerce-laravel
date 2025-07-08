<?php

namespace Tests\Unit;

use App\Models\Currency;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_wallet_belongs_to_user()
    {
        $user = User::factory()->create();
        $currency = Currency::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'currency_id' => $currency->id
        ]);

        $this->assertInstanceOf(User::class, $wallet->user);
        $this->assertEquals($user->id, $wallet->user->id);
    }

    public function test_wallet_belongs_to_currency()
    {
        $user = User::factory()->create();
        $currency = Currency::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'currency_id' => $currency->id
        ]);

        $this->assertInstanceOf(Currency::class, $wallet->currency);
        $this->assertEquals($currency->id, $wallet->currency->id);
    }

    public function test_wallet_balance_is_formatted_correctly()
    {
        $user = User::factory()->create();
        $currency = Currency::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'balance' => 1.23456789
        ]);

        $this->assertEquals('1.23456789', $wallet->balance);
    }

    public function test_wallet_can_add_balance()
    {
        $user = User::factory()->create();
        $currency = Currency::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'balance' => 1.0
        ]);

        $wallet->addBalance(0.5);

        $this->assertEquals(1.5, $wallet->fresh()->balance);
    }

    public function test_wallet_can_subtract_balance()
    {
        $user = User::factory()->create();
        $currency = Currency::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'balance' => 1.0
        ]);

        $result = $wallet->subtractBalance(0.3);

        $this->assertTrue($result);
        $this->assertEquals(0.7, $wallet->fresh()->balance);
    }

    public function test_wallet_cannot_subtract_more_than_balance()
    {
        $user = User::factory()->create();
        $currency = Currency::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'balance' => 0.5
        ]);

        $result = $wallet->subtractBalance(1.0);

        $this->assertFalse($result);
        $this->assertEquals(0.5, $wallet->fresh()->balance);
    }

    public function test_wallet_has_sufficient_balance()
    {
        $user = User::factory()->create();
        $currency = Currency::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'balance' => 1.0
        ]);

        $this->assertTrue($wallet->hasSufficientBalance(0.5));
        $this->assertTrue($wallet->hasSufficientBalance(1.0));
        $this->assertFalse($wallet->hasSufficientBalance(1.5));
    }
}
