<?php

declare(strict_types=1);

namespace Modules\OpenAI\Application;

use Modules\OpenAI\Domain\Data\AiProviderDefinition;
use Modules\OpenAI\Domain\Data\ModelSelection;
use Modules\OpenAI\Domain\Enum\AiProvider;
use Modules\OpenAI\Domain\Exception\OpenAIError;
use Modules\OpenAI\Domain\Exception\UnsupportedModelError;
use Modules\OpenAI\Domain\ProviderRegistryInterface;

use function sprintf;
use function trim;

final readonly class ProviderRegistry implements ProviderRegistryInterface
{
    /**
     * Keys are opaque bearer tokens; every provider in use issues them from
     * this alphabet. Rejecting anything else keeps CR/LF out of the
     * Authorization header and bounds what an attacker can push upstream.
     */
    private const USER_KEY_PATTERN = '/^[A-Za-z0-9._\-]{16,256}$/';

    /**
     * Preference order for the automatic default: a provider whose free tier
     * costs nothing to call comes before the paid one. Without this the app
     * defaults to OpenAI even on a deployment that only holds a free-tier key,
     * and every chat fails on a missing or exhausted OpenAI account.
     */
    private const DEFAULT_PROVIDER_PREFERENCE = [
        AiProvider::Groq,
        AiProvider::OpenRouter,
        AiProvider::OpenAI,
    ];

    public function __construct(
        private string $openAiBaseUrl,
        private string $openAiApiKey,
        private string $openAiModel,
        private string $openAiModelFollowup,
        private string $openRouterApiKey = '',
        private string $groqApiKey = '',
    ) {
    }

    /**
     * @return list<AiProviderDefinition>
     */
    public function all(): array
    {
        return array_map(
            fn (AiProvider $provider): AiProviderDefinition => $this->definition($provider),
            AiProvider::cases(),
        );
    }

    public function find(string $providerId): ?AiProviderDefinition
    {
        $provider = AiProvider::tryFrom($providerId);

        return $provider instanceof AiProvider ? $this->definition($provider) : null;
    }

    public function defaultSelection(): ModelSelection
    {
        $definition = $this->defaultDefinition();

        return $this->build($definition, $this->defaultModelFor($definition, $this->openAiModel), null);
    }

    public function defaultFollowupSelection(): ModelSelection
    {
        $definition = $this->defaultDefinition();

        return $this->build($definition, $this->defaultModelFor($definition, $this->openAiModelFollowup), null);
    }

    public function selectionFrom(
        ?string $providerId,
        ?string $modelId,
        ?string $userApiKey = null,
    ): ModelSelection {
        $providerId = trim($providerId ?? '');
        $modelId = trim($modelId ?? '');

        if ($providerId === '') {
            // "Automatic" — the same default defaultSelection() resolves to.
            $definition = $this->defaultDefinition();
            $model = $modelId === '' ? $this->defaultModelFor($definition, $this->openAiModel) : $modelId;

            if ($modelId !== '' && !$definition->supports($modelId)) {
                throw $this->unsupportedModel($definition, $modelId);
            }

            // A key belongs to exactly one provider, so it cannot be honoured
            // when none was named: "let the app choose" and "use my key" are
            // contradictory instructions. The key is dropped rather than sent
            // to whichever provider Automatic happens to resolve to.
            return $this->build($definition, $model, null);
        }

        $definition = $this->find($providerId);

        if (!$definition instanceof AiProviderDefinition) {
            throw new UnsupportedModelError(sprintf('Unsupported AI provider "%s".', $providerId));
        }

        if ($modelId === '') {
            return $this->build($definition, $definition->defaultModel()->id, $userApiKey);
        }

        if (!$definition->supports($modelId)) {
            throw $this->unsupportedModel($definition, $modelId);
        }

        return $this->build($definition, $modelId, $userApiKey);
    }

    /**
     * The first preferred provider this deployment actually holds a key for.
     * Falls back to OpenAI so a wholly unconfigured install still fails with
     * "OPENAI_API_KEY is not configured" rather than something obscure.
     */
    private function defaultDefinition(): AiProviderDefinition
    {
        foreach (self::DEFAULT_PROVIDER_PREFERENCE as $provider) {
            if ($this->serverKey($provider) !== '') {
                return $this->definition($provider);
            }
        }

        return $this->definition(AiProvider::OpenAI);
    }

    /**
     * The OpenAI model settings only describe OpenAI, so they are meaningless
     * once the default lands on another provider — that one picks its own free
     * model instead.
     */
    private function defaultModelFor(AiProviderDefinition $definition, string $configuredModel): string
    {
        if ($definition->provider === AiProvider::OpenAI) {
            return $configuredModel;
        }

        return $definition->defaultFreeModel()->id;
    }

    private function definition(AiProvider $provider): AiProviderDefinition
    {
        return new AiProviderDefinition(
            $provider,
            $this->baseUrl($provider),
            $provider->models(),
            $this->serverKey($provider) !== '',
        );
    }

    /**
     * Only the OpenAI base URL is configurable — the other two are pinned to
     * the enum so no setting can redirect them somewhere unexpected.
     */
    private function baseUrl(AiProvider $provider): string
    {
        if ($provider === AiProvider::OpenAI && trim($this->openAiBaseUrl) !== '') {
            return trim($this->openAiBaseUrl);
        }

        return $provider->defaultBaseUrl();
    }

    private function serverKey(AiProvider $provider): string
    {
        return match ($provider) {
            AiProvider::OpenAI => $this->openAiApiKey,
            AiProvider::OpenRouter => $this->openRouterApiKey,
            AiProvider::Groq => $this->groqApiKey,
        };
    }

    private function build(
        AiProviderDefinition $definition,
        string $model,
        ?string $userApiKey,
    ): ModelSelection {
        $userApiKey = trim($userApiKey ?? '');

        if ($userApiKey !== '') {
            if (preg_match(self::USER_KEY_PATTERN, $userApiKey) !== 1) {
                // Never echo the key back, not even a prefix of it.
                throw new UnsupportedModelError('The provided API key has an unexpected format.');
            }

            return new ModelSelection($definition, $model, $userApiKey, usesUserKey: true);
        }

        $serverKey = $this->serverKey($definition->provider);

        if ($serverKey === '') {
            throw new OpenAIError(sprintf('%s is not configured.', $definition->provider->apiKeyEnvName()));
        }

        return new ModelSelection($definition, $model, $serverKey);
    }

    private function unsupportedModel(AiProviderDefinition $definition, string $modelId): UnsupportedModelError
    {
        return new UnsupportedModelError(sprintf(
            'Unsupported model "%s" for provider "%s".',
            $modelId,
            $definition->id(),
        ));
    }
}
