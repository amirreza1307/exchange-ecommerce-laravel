<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'currency_id',
        'balance',
        'frozen_balance'
    ];

    protected $casts = [
        'balance' => 'decimal:8',
        'frozen_balance' => 'decimal:8'
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    // Methods
    public function getAvailableBalanceAttribute()
    {
        return $this->balance - $this->frozen_balance;
    }

    public function hasEnoughBalance($amount)
    {
        return $this->getAvailableBalanceAttribute() >= $amount;
    }

    public function addBalance($amount)
    {
        $this->increment('balance', $amount);
        return $this;
    }

    public function subtractBalance($amount)
    {
        if (!$this->hasEnoughBalance($amount)) {
            return false;
        }
        $this->decrement('balance', $amount);
        return true;
    }

    public function hasSufficientBalance($amount)
    {
        return $this->balance >= $amount;
    }

    public function freezeBalance($amount)
    {
        if (!$this->hasEnoughBalance($amount)) {
            throw new \Exception('Insufficient balance');
        }
        $this->increment('frozen_balance', $amount);
        $this->decrement('balance', $amount);
        return $this;
    }

    public function unfreezeBalance($amount)
    {
        $this->decrement('frozen_balance', $amount);
        return $this;
    }
}
