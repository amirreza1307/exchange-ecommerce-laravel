<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'symbol',
        'name',
        'description',
        'image',
        'buy_price',
        'sell_price',
        'buy_commission',
        'sell_commission',
        'treasury_balance',
        'is_active',
        'is_tradeable',
        'decimal_places'
    ];

    protected $casts = [
        'buy_price' => 'decimal:8',
        'sell_price' => 'decimal:8',
        'buy_commission' => 'decimal:2',
        'sell_commission' => 'decimal:2',
        'treasury_balance' => 'decimal:8',
        'is_active' => 'boolean',
        'is_tradeable' => 'boolean',
        'decimal_places' => 'integer'
    ];

    // Relations
    public function wallets()
    {
        return $this->hasMany(Wallet::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function fromExchangeRates()
    {
        return $this->hasMany(ExchangeRate::class, 'from_currency_id');
    }

    public function toExchangeRates()
    {
        return $this->hasMany(ExchangeRate::class, 'to_currency_id');
    }

    public function fromOrders()
    {
        return $this->hasMany(Order::class, 'from_currency_id');
    }

    public function toOrders()
    {
        return $this->hasMany(Order::class, 'to_currency_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeTradeable($query)
    {
        return $query->where('is_tradeable', true);
    }

    // Methods
    public function formatAmount($amount)
    {
        return number_format($amount, $this->decimal_places);
    }

    public function hasEnoughTreasuryBalance($amount)
    {
        return $this->treasury_balance >= $amount;
    }
}
