<?php
declare(strict_types=1);

namespace Modules\Faq;

use Illuminate\Support\ServiceProvider;
use Modules\Faq\Domain\Repository\FaqRepositoryInterface;
use Modules\Faq\Infrastructure\Command\ImportFaqs;
use Modules\Faq\Infrastructure\Repository\FaqRepository;
use Override;

final class FaqServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public $singletons = [
        FaqRepositoryInterface::class => FaqRepository::class,
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

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ImportFaqs::class,
            ]);
        }
    }
}
