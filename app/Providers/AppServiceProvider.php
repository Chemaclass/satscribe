<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\SatscribeAction;
use App\Http\Middleware\IpRateLimiter;
use App\Services\OpenAIService;
use App\Services\PriceService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app
            ->when(SatscribeAction::class)
            ->needs('$ip')
            ->give(request()->ip());

        $this->app
            ->when(SatscribeAction::class)
            ->needs('$maxOpenAIAttempts')
            ->giveConfig('app.max_open_ai_attempts');

        $this->app
            ->when(OpenAIService::class)
            ->needs('$openAiApiKey')
            ->giveConfig('services.openai.key');

        $this->app
            ->when(OpenAIService::class)
            ->needs('$openAiModel')
            ->giveConfig('services.openai.model');

        $this->app
            ->when(PriceService::class)
            ->needs('$enabled')
            ->giveConfig('features.btc_price');

        $this->app
            ->when(IpRateLimiter::class)
            ->needs('$maxAttempts')
            ->giveConfig('app.max_ip_rate_limit_attempts');;
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useTailwind();

        View::share('cronitorClientKey', config('app.cronitorClientKey'));
        View::share('btcPriceUsd', app(PriceService::class)->getCurrentBtcPriceUsd());;
    }
}
