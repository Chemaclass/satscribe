<?php

namespace App\Providers;

use App\Actions\DescribePromptResultAction;
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

        $this->app
            ->when(DescribePromptResultAction::class)
            ->needs('$maxOpenAIAttempts')
            ->giveConfig('app.max_open_ai_attempts');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useTailwind();

        RateLimiter::for('generate', function ($request) {
            return Limit::perDay(config('app.rate_limit_generate'))->by($request->ip());
        });
    }
}
