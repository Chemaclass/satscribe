<?php

declare(strict_types=1);

namespace Modules\OpenAI;

use Illuminate\Support\ServiceProvider;
use Modules\OpenAI\Application\OfflineFallback;
use Modules\OpenAI\Application\OfflineNarrator;
use Modules\OpenAI\Application\OpenAIFacade;
use Modules\OpenAI\Application\PersonaPromptBuilder;
use Modules\OpenAI\Application\ProviderRegistry;
use Modules\OpenAI\Domain\OfflineNarratorInterface;
use Modules\OpenAI\Domain\OpenAIFacadeInterface;
use Modules\OpenAI\Domain\ProviderRegistryInterface;
use Override;

final class OpenAIServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public $singletons = [
        OfflineNarratorInterface::class => OfflineNarrator::class,
        OpenAIFacadeInterface::class => OpenAIFacade::class,
        ProviderRegistryInterface::class => ProviderRegistry::class,
    ];

    /** @var array<class-string, class-string> */
    public $bindings = [];

    #[Override]
    public function register(): void
    {
        $this->app->when(OfflineFallback::class)
            ->needs('$enabled')
            ->giveConfig('services.ai_offline_fallback');

        $this->app->when(ProviderRegistry::class)
            ->needs('$openAiBaseUrl')
            ->giveConfig('services.openai.base_url');

        $this->app->when(ProviderRegistry::class)
            ->needs('$openAiApiKey')
            ->giveConfig('services.openai.key');

        $this->app->when(ProviderRegistry::class)
            ->needs('$openAiModel')
            ->giveConfig('services.openai.model');

        $this->app->when(ProviderRegistry::class)
            ->needs('$openAiModelFollowup')
            ->giveConfig('services.openai.model_followup');

        $this->app->when(ProviderRegistry::class)
            ->needs('$openRouterApiKey')
            ->giveConfig('services.openrouter.key');

        $this->app->when(ProviderRegistry::class)
            ->needs('$groqApiKey')
            ->giveConfig('services.groq.key');

        $this->app->when(PersonaPromptBuilder::class)
            ->needs('$locale')
            ->give(app()->getLocale());
    }
}
