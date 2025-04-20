<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\DescribePromptResultAction;
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
            ->when(DescribePromptResultAction::class)
            ->needs('$ip')
            ->give(request()->ip());

        $this->app
            ->when(DescribePromptResultAction::class)
            ->needs('$maxOpenAIAttempts')
            ->giveConfig('app.max_open_ai_attempts');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useTailwind();

        View::share('cronitorClientKey', config('app.cronitorClientKey'));
    }
}
