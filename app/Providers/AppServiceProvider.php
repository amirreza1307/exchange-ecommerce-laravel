<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\User;
use App\Models\Currency;
use App\Observers\UserObserver;
use App\Observers\CurrencyObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS in production
        if ($this->app->environment('production')) {
            \URL::forceScheme('https');
        }

        // Register observers only in non-testing environments
        // This prevents conflicts with factory-created data in tests
        if (!app()->runningUnitTests()) {
            User::observe(UserObserver::class);
            Currency::observe(CurrencyObserver::class);
        }
    }
}
