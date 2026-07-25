<?php

declare(strict_types=1);

namespace Modules\OpenAI\Domain;

use Modules\OpenAI\Domain\Data\AiProviderDefinition;
use Modules\OpenAI\Domain\Data\ModelSelection;
use Modules\OpenAI\Domain\Exception\OpenAIError;
use Modules\OpenAI\Domain\Exception\UnsupportedModelError;

/**
 * The allowlist of providers and models, and the only place a request-supplied
 * provider/model string is turned into something the app will call.
 */
interface ProviderRegistryInterface
{
    /**
     * @return list<AiProviderDefinition>
     */
    public function all(): array;

    public function find(string $providerId): ?AiProviderDefinition;

    /**
     * The configured default — what every request used before model selection
     * existed.
     *
     * @throws OpenAIError when no key is configured for the default provider
     */
    public function defaultSelection(): ModelSelection;

    /**
     * The configured follow-up default, used for messages inside an existing chat.
     *
     * @throws OpenAIError when no key is configured for the default provider
     */
    public function defaultFollowupSelection(): ModelSelection;

    /**
     * Validates a request-supplied selection against the allowlist. A blank
     * provider or model falls back to the configured default.
     *
     * @throws UnsupportedModelError when the provider/model is not allowlisted
     *                               or the supplied key is malformed
     * @throws OpenAIError when no key is available for the chosen provider
     */
    public function selectionFrom(
        ?string $providerId,
        ?string $modelId,
        ?string $userApiKey = null,
    ): ModelSelection;
}
