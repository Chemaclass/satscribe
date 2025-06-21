<?php
declare(strict_types=1);

namespace Modules\Blockchain;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Modules\Blockchain\Application\BlockchainFacade;
use Modules\Blockchain\Application\BlockchainService;
use Modules\Blockchain\Application\BlockHeightProvider;
use Modules\Blockchain\Application\PriceService;
use Modules\Blockchain\Domain\BlockchainFacadeInterface;
use Modules\Blockchain\Domain\BlockchainServiceInterface;
use Override;

final class BlockchainServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public $singletons = [
        BlockchainServiceInterface::class => BlockchainService::class,
        BlockchainFacadeInterface::class => BlockchainFacade::class,
    ];

    /**
     * @var array<class-string, class-string>
     */
    public $bindings = [];

    /**
     * Register any application services.
     */
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
        $priceService = app(PriceService::class);
        View::share('btcPriceUsd', $priceService->getCurrentBtcPriceUsd());
        View::share('btcPriceEur', $priceService->getCurrentBtcPriceEur());
        View::share('btcPriceCny', $priceService->getCurrentBtcPriceCny());
        View::share('btcPriceGbp', $priceService->getCurrentBtcPriceGbp());
    }
}
