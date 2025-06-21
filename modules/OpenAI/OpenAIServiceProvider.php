<?php
declare(strict_types=1);

namespace Modules\OpenAI;

use Illuminate\Support\ServiceProvider;
use Modules\OpenAI\Application\OpenAIService;
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
        $this->app->when(OpenAIService::class)
            ->needs('$openAiApiKey')
            ->giveConfig('services.openai.key');

        $this->app->when(OpenAIService::class)
            ->needs('$openAiModel')
            ->giveConfig('services.openai.model');
    }
}
