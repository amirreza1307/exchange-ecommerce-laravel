<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'national_id',
        'password',
        'role',
        'is_active',
        'rial_balance',
        'bank_account',
        'bank_name',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'rial_balance' => 'decimal:2',
        ];
    }

    // Relations
    public function wallets()
    {
        return $this->hasMany(Wallet::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    // Methods
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function getWalletForCurrency($currencyId)
    {
        return $this->wallets()->where('currency_id', $currencyId)->first();
    }

    public function getOrCreateWallet($currencyId)
    {
        return $this->wallets()->firstOrCreate(
            ['currency_id' => $currencyId],
            ['balance' => 0, 'frozen_balance' => 0]
        );
    }

    public function addRialBalance($amount)
    {
        $this->increment('rial_balance', $amount);
        return $this;
    }

    public function subtractRialBalance($amount)
    {
        if ($this->rial_balance < $amount) {
            throw new \Exception('Insufficient rial balance');
        }
        $this->decrement('rial_balance', $amount);
        return $this;
    }

    public function hasEnoughRialBalance($amount)
    {
        return $this->rial_balance >= $amount;
    }
}
