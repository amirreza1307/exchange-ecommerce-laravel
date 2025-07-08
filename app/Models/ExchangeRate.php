<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_currency_id',
        'to_currency_id',
        'rate',
        'buy_rate',
        'sell_rate',
        'is_active',
        'last_updated'
    ];

    protected $casts = [
        'rate' => 'decimal:8',
        'buy_rate' => 'decimal:8',
        'sell_rate' => 'decimal:8',
        'is_active' => 'boolean',
        'last_updated' => 'datetime'
    ];

    // Relations
    public function fromCurrency()
    {
        return $this->belongsTo(Currency::class, 'from_currency_id');
    }

    public function toCurrency()
    {
        return $this->belongsTo(Currency::class, 'to_currency_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForPair($query, $fromCurrencyId, $toCurrencyId)
    {
        return $query->where('from_currency_id', $fromCurrencyId)
                    ->where('to_currency_id', $toCurrencyId);
    }

    // Methods
    public function updateRate($rate, $buyRate = null, $sellRate = null)
    {
        $this->update([
            'rate' => $rate,
            'buy_rate' => $buyRate ?? $rate,
            'sell_rate' => $sellRate ?? $rate,
            'last_updated' => now()
        ]);
    }

    public function getRateForType($type = 'rate')
    {
        return $this->{$type} ?? $this->rate;
    }
}
