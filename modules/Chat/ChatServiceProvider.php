<?php
declare(strict_types=1);

use Illuminate\Support\ServiceProvider;

final class ChatServiceProvider extends ServiceProvider
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
