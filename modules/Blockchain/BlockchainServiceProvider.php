<?php

declare(strict_types=1);

namespace Modules\Blockchain;

use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Modules\Blockchain\Application\BlockchainFacade;
use Modules\Blockchain\Application\Blockstream\BlockHeightProvider;
use Modules\Blockchain\Application\Coingecko\PriceService;
use Modules\Blockchain\Domain\BlockchainFacadeInterface;
use Modules\Blockchain\Domain\PriceServiceInterface;
use Override;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class BlockchainServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public $singletons = [
        BlockchainFacadeInterface::class => BlockchainFacade::class,
        PriceServiceInterface::class => PriceService::class,
    ];

    /** @var array<class-string, class-string> */
    public $bindings = [];

    #[Override]
    public function register(): void
    {
        $this->app->when(BlockHeightProvider::class)
            ->needs('$enabled')
            ->giveConfig('features.btc_block_height');

        $this->app->when(PriceService::class)
            ->needs('$enabled')
            ->giveConfig('features.btc_price');
    }

    public function boot(): void
    {
        // Composed onto the layout rather than shared from boot(): boot() runs
        // on every request, including /up, and a health check that waits on
        // Coingecko is a health check that fails when Coingecko does.
        View::composer('layouts.base', function (ViewContract $view): void {
            $view->with($this->currentPrices());
        });
    }

    /**
     * Coingecko rate-limits its free tier and times out, so an unguarded lookup
     * here turns a missing price badge into a site-wide 500. The ticker is
     * decoration: degrade it to zeros and keep serving.
     *
     * @return array<string, float>
     */
    private function currentPrices(): array
    {
        $priceService = app(PriceServiceInterface::class);

        try {
            return [
                'btcPriceUsd' => $priceService->getCurrentBtcPriceUsd(),
                'btcPriceEur' => $priceService->getCurrentBtcPriceEur(),
                'btcPriceCny' => $priceService->getCurrentBtcPriceCny(),
                'btcPriceGbp' => $priceService->getCurrentBtcPriceGbp(),
            ];
        } catch (RuntimeException $e) {
            app(LoggerInterface::class)->warning('BTC price unavailable', [
                'error' => $e->getMessage(),
            ]);

            return [
                'btcPriceUsd' => 0.0,
                'btcPriceEur' => 0.0,
                'btcPriceCny' => 0.0,
                'btcPriceGbp' => 0.0,
            ];
        }
    }
}
