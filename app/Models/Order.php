<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'user_id',
        'type',
        'from_currency_id',
        'to_currency_id',
        'from_amount',
        'to_amount',
        'exchange_rate',
        'commission_rate',
        'commission_amount',
        'final_amount',
        'status',
        'discount_code',
        'discount_amount',
        'metadata',
        'processed_at',
        'cancellation_reason'
    ];

    protected $casts = [
        'from_amount' => 'decimal:8',
        'to_amount' => 'decimal:8',
        'exchange_rate' => 'decimal:8',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:8',
        'final_amount' => 'decimal:8',
        'discount_amount' => 'decimal:8',
        'metadata' => 'array',
        'processed_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = 'ORD-' . date('YmdHis') . '-' . rand(1000, 9999);
            }
        });
    }

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function fromCurrency()
    {
        return $this->belongsTo(Currency::class, 'from_currency_id');
    }

    public function toCurrency()
    {
        return $this->belongsTo(Currency::class, 'to_currency_id');
    }

    public function discount()
    {
        return $this->belongsTo(Discount::class, 'discount_code', 'code');
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Methods
    public function markAsProcessing()
    {
        $this->update(['status' => 'processing']);
    }

    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
            'processed_at' => now()
        ]);
    }

    public function markAsFailed()
    {
        $this->update(['status' => 'failed']);
    }

    public function cancel($reason = null)
    {
        $this->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason
        ]);
    }

    public function calculateTotal()
    {
        return $this->to_amount - $this->commission_amount - $this->discount_amount;
    }
}
