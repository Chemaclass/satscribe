<?php

declare(strict_types=1);

namespace Modules\Shared;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Modules\Shared\Application\HttpClient;
use Modules\Shared\Domain\HttpClientInterface;
use Modules\Shared\Infrastructure\Http\Middleware\IpRateLimiter;
use Override;

final class SharedServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public $singletons = [
        HttpClientInterface::class => HttpClient::class,
    ];

    /** @var array<class-string, class-string> */
    public $bindings = [];

    /**
     * Register any application services.
     */
    #[Override]
    public function register(): void
    {
        $this->app->bind(CarbonInterface::class, static fn () => Carbon::now());

        $this->app->when(IpRateLimiter::class)
            ->needs('$maxAttempts')
            ->giveConfig('services.rate_limit.max_attempts');

        $this->app->when(IpRateLimiter::class)
            ->needs('$lnInvoiceAmountInSats')
            ->giveConfig('services.rate_limit.invoice_amount');

        $this->app->when(IpRateLimiter::class)
            ->needs('$lnInvoiceExpirySeconds')
            ->giveConfig('services.rate_limit.invoice_expiry');
    }

    public function boot(): void
    {
        if (app()->environment('prod')) {
            URL::forceScheme('https');
        }

        Paginator::useTailwind();

        View::share('cronitorClientKey', config('app.cronitorClientKey'));
    }
}
