<?php

namespace Tests\Unit;

use App\Models\Currency;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_belongs_to_user()
    {
        $user = User::factory()->create();
        $currency = Currency::factory()->create();
        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'currency_id' => $currency->id
        ]);

        $this->assertInstanceOf(User::class, $transaction->user);
        $this->assertEquals($user->id, $transaction->user->id);
    }

    public function test_transaction_belongs_to_currency()
    {
        $user = User::factory()->create();
        $currency = Currency::factory()->create();
        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'currency_id' => $currency->id
        ]);

        $this->assertInstanceOf(Currency::class, $transaction->currency);
        $this->assertEquals($currency->id, $transaction->currency->id);
    }

    public function test_transaction_has_correct_scopes()
    {
        $user = User::factory()->create();
        $currency = Currency::factory()->create();
        
        // Create transactions with different types
        Transaction::factory()->create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'type' => 'buy'
        ]);
        
        Transaction::factory()->create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'type' => 'sell'
        ]);
        
        Transaction::factory()->create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'type' => 'deposit'
        ]);

        $this->assertEquals(1, Transaction::ofType('buy')->count());
        $this->assertEquals(1, Transaction::ofType('sell')->count());
        $this->assertEquals(1, Transaction::ofType('deposit')->count());
    }

    public function test_transaction_status_scopes()
    {
        $user = User::factory()->create();
        $currency = Currency::factory()->create();
        
        Transaction::factory()->create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'status' => 'completed'
        ]);
        
        Transaction::factory()->create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'status' => 'pending'
        ]);
        
        Transaction::factory()->create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'status' => 'failed'
        ]);

        $this->assertEquals(1, Transaction::completed()->count());
        $this->assertEquals(1, Transaction::pending()->count());
        $this->assertEquals(1, Transaction::failed()->count());
    }

    public function test_transaction_can_be_marked_as_completed()
    {
        $user = User::factory()->create();
        $currency = Currency::factory()->create();
        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'status' => 'pending'
        ]);

        $transaction->markAsCompleted();

        $this->assertEquals('completed', $transaction->fresh()->status);
    }

    public function test_transaction_can_be_marked_as_failed()
    {
        $user = User::factory()->create();
        $currency = Currency::factory()->create();
        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'status' => 'pending'
        ]);

        $transaction->markAsFailed();

        $this->assertEquals('failed', $transaction->fresh()->status);
    }

    public function test_transaction_amount_is_formatted_correctly()
    {
        $user = User::factory()->create();
        $currency = Currency::factory()->create();
        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'amount' => 1.23456789,
            'fee' => 0.01,
            'final_amount' => 1.22456789
        ]);

        $this->assertEquals('1.23456789', $transaction->amount);
        $this->assertEquals('0.01000000', $transaction->fee);
        $this->assertEquals('1.22456789', $transaction->final_amount);
    }

    public function test_transaction_is_deposit_method()
    {
        $user = User::factory()->create();
        $currency = Currency::factory()->create();
        
        $depositTransaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'type' => 'deposit'
        ]);
        
        $buyTransaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'type' => 'buy'
        ]);

        $this->assertTrue($depositTransaction->isDeposit());
        $this->assertFalse($buyTransaction->isDeposit());
    }

    public function test_transaction_is_withdrawal_method()
    {
        $user = User::factory()->create();
        $currency = Currency::factory()->create();
        
        $withdrawalTransaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'type' => 'withdraw'
        ]);
        
        $sellTransaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'type' => 'sell'
        ]);

        $this->assertTrue($withdrawalTransaction->isWithdrawal());
        $this->assertFalse($sellTransaction->isWithdrawal());
    }

    public function test_transaction_is_trade_method()
    {
        $user = User::factory()->create();
        $currency = Currency::factory()->create();
        
        $buyTransaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'type' => 'buy'
        ]);
        
        $sellTransaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'type' => 'sell'
        ]);
        
        $depositTransaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'type' => 'deposit'
        ]);

        $this->assertTrue($buyTransaction->isTrade());
        $this->assertTrue($sellTransaction->isTrade());
        $this->assertFalse($depositTransaction->isTrade());
    }
}
