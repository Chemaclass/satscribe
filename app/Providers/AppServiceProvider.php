<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\SatscribeAction;
use App\Services\PriceService;
use Illuminate\Http\Client\Factory;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
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

        $this->app->singleton(PriceService::class, function () {
            return new PriceService(
                app(Factory::class),
                Log::getLogger(),
                Cache::store(),
                config('features.btc_price', false),
            );
        });
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
