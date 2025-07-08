<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'currency_id',
        'type',
        'amount',
        'fee',
        'final_amount',
        'status',
        'reference_id',
        'metadata',
        'description',
        'processed_at'
    ];

    protected $casts = [
        'amount' => 'decimal:8',
        'fee' => 'decimal:8',
        'final_amount' => 'decimal:8',
        'metadata' => 'array',
        'processed_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($transaction) {
            if (empty($transaction->uuid)) {
                $transaction->uuid = (string) Str::uuid();
            }
        });
    }

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
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

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Methods
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

    public function markAsCancelled()
    {
        $this->update(['status' => 'cancelled']);
    }

    public function isDeposit()
    {
        return $this->type === 'deposit';
    }

    public function isWithdrawal()
    {
        return $this->type === 'withdraw';
    }

    public function isTrade()
    {
        return in_array($this->type, ['buy', 'sell']);
    }
}
