<?php

namespace App\Observers;

use App\Models\Currency;
use App\Models\User;
use App\Models\Wallet;

class CurrencyObserver
{
    /**
     * Handle the Currency "created" event.
     */
    public function created(Currency $currency): void
    {
        // Create wallets for all users when a new currency is created
        $users = User::all();
        
        foreach ($users as $user) {
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
     * Handle the Currency "updated" event.
     */
    public function updated(Currency $currency): void
    {
        //
    }

    /**
     * Handle the Currency "deleted" event.
     */
    public function deleted(Currency $currency): void
    {
        //
    }

    /**
     * Handle the Currency "restored" event.
     */
    public function restored(Currency $currency): void
    {
        //
    }

    /**
     * Handle the Currency "force deleted" event.
     */
    public function forceDeleted(Currency $currency): void
    {
        //
    }
}
