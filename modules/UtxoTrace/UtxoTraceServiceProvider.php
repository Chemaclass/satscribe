<?php
declare(strict_types=1);

namespace Modules\UtxoTrace;

use Illuminate\Support\ServiceProvider;
use Modules\UtxoTrace\Application\UtxoTraceFacade;
use Modules\UtxoTrace\Domain\Repository\UtxoTraceRepositoryInterface;
use Modules\UtxoTrace\Domain\UtxoTraceFacadeInterface;
use Modules\UtxoTrace\Infrastructure\Repository\UtxoTraceRepository;
use Override;

final class UtxoTraceServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public $singletons = [
        UtxoTraceRepositoryInterface::class => UtxoTraceRepository::class,
        UtxoTraceFacadeInterface::class => UtxoTraceFacade::class,
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
    }
}
