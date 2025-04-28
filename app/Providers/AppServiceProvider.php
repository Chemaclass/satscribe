<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\AlbySettleWebhookAction;
use App\Actions\SatscribeAction;
use App\Http\Middleware\IpRateLimiter;
use App\Services\Alby\AlbyClient;
use App\Services\Alby\AlbyClientInterface;
use App\Services\BlockHeightProvider;
use App\Services\OpenAIService;
use App\Services\PriceService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public $singletons = [
        AlbyClientInterface::class => AlbyClient::class,
    ];

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
            ->giveConfig('services.openai.max_attempts');

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
            ->when(BlockHeightProvider::class)
            ->needs('$enabled')
            ->giveConfig('features.btc_block_height');

        $this->app
            ->when(IpRateLimiter::class)
            ->needs('$maxAttempts')
            ->giveConfig('services.rate_limit.max_attempts');

        $this->app
            ->when(IpRateLimiter::class)
            ->needs('$lnInvoiceAmountInSats')
            ->giveConfig('services.rate_limit.invoice_amount');

        $this->app
            ->when(IpRateLimiter::class)
            ->needs('$lnInvoiceExpirySeconds')
            ->giveConfig('services.rate_limit.invoice_expiry');

        $this->app
            ->when(AlbyClient::class)
            ->needs('$accessToken')
            ->giveConfig('services.alby.api_key');
        $this->app
            ->when(AlbySettleWebhookAction::class)
            ->needs('$webhookSecret')
            ->giveConfig('services.alby.webhook_secret');
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
