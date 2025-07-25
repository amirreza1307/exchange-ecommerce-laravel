<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Currency;
use App\Models\Wallet;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // Create wallets for all active currencies
        $currencies = Currency::active()->get();
        
        foreach ($currencies as $currency) {
            // Check if wallet already exists to avoid duplicates
            Wallet::firstOrCreate([
                'user_id' => $user->id,
                'currency_id' => $currency->id,
            ], [
                'balance' => 0,
                'frozen_balance' => 0
            ]);
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        //
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
