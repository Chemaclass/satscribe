<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useTailwind();

        RateLimiter::for('generate', function ($request) {
            return Limit::perDay(1000)->by($request->ip());
        });

        RateLimiter::for('openai', function ($request) {
            return Limit::perDay(50)->by($request->ip());
        });
    }
}
