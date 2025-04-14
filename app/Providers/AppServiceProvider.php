<?php

namespace App\Providers;

use App\Actions\DescribePromptResultAction;
use App\Services\OpenAIService;
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
        $this->app
            ->when(DescribePromptResultAction::class)
            ->needs('$ip')
            ->give(request()->ip());
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
    }
}
