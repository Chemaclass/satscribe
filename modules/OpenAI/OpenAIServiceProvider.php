<?php
declare(strict_types=1);

namespace Modules\OpenAI;

use Illuminate\Support\ServiceProvider;
use Override;

final class OpenAIServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public $singletons = [];

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
    }
}
