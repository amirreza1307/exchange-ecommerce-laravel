<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Discount extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'title',
        'description',
        'type',
        'value',
        'min_order_amount',
        'max_discount_amount',
        'usage_limit',
        'used_count',
        'user_usage_limit',
        'is_active',
        'starts_at',
        'expires_at'
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_order_amount' => 'decimal:8',
        'max_discount_amount' => 'decimal:8',
        'usage_limit' => 'integer',
        'used_count' => 'integer',
        'user_usage_limit' => 'integer',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime'
    ];

    // Relations
    public function orders()
    {
        return $this->hasMany(Order::class, 'discount_code', 'code');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeNotExpired($query)
    {
        return $query->where('expires_at', '>', now());
    }

    public function scopeAvailable($query)
    {
        return $query->active()
                    ->notExpired()
                    ->where(function ($q) {
                        $q->whereNull('usage_limit')
                          ->orWhereRaw('used_count < usage_limit');
                    });
    }

    // Methods
    public function isExpired()
    {
        return $this->expires_at && $this->expires_at < now();
    }

    public function isMaxUsageReached()
    {
        return $this->usage_limit && $this->used_count >= $this->usage_limit;
    }

    public function isAvailable()
    {
        return $this->is_active && !$this->isExpired() && !$this->isMaxUsageReached();
    }

    public function isValid()
    {
        return $this->isAvailable();
    }

    public function isValidForAmount($amount)
    {
        return $this->isValid() 
               && ($this->min_order_amount === null || $amount >= $this->min_order_amount);
    }

    public function canBeUsed($amount)
    {
        return $this->isValidForAmount($amount);
    }

    public function calculateDiscount($amount)
    {
        if (!$this->isValidForAmount($amount)) {
            return 0;
        }

        $discount = 0;
        
        if ($this->type === 'percentage') {
            $discount = ($amount * $this->value) / 100;
        } else {
            $discount = $this->value;
        }

        if ($this->max_discount_amount && $discount > $this->max_discount_amount) {
            $discount = $this->max_discount_amount;
        }

        return $discount;
    }

    public function incrementUsage()
    {
        $this->increment('used_count');
    }

    public function use()
    {
        $this->incrementUsage();
    }

    public static function findByCode($code)
    {
        return static::where('code', $code)->available()->first();
    }
}
