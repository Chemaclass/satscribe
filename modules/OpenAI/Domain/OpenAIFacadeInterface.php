<?php

declare(strict_types=1);

namespace Modules\OpenAI\Domain;

use App\Models\Chat;
use Generator;
use Modules\OpenAI\Domain\Data\AiProviderDefinition;
use Modules\OpenAI\Domain\Data\ModelSelection;
use Modules\OpenAI\Domain\Exception\UnsupportedModelError;
use Modules\Shared\Domain\Data\Blockchain\BlockchainData;
use Modules\Shared\Domain\Data\Chat\PromptInput;
use Modules\Shared\Domain\Enum\Chat\PromptPersona;

interface OpenAIFacadeInterface
{
    public function generateText(
        BlockchainData $data,
        PromptInput $input,
        PromptPersona $persona,
        string $question,
        ?Chat $chat = null,
        string $additionalContext = '',
        ?ModelSelection $selection = null,
    ): string;

    /**
     * @return Generator<string>
     */
    public function generateTextStreaming(
        BlockchainData $data,
        PromptInput $input,
        PromptPersona $persona,
        string $question,
        ?Chat $chat = null,
        string $additionalContext = '',
        ?ModelSelection $selection = null,
    ): Generator;

    /**
     * Providers and models this deployment accepts — the allowlist a model
     * picker should be built from.
     *
     * @return list<AiProviderDefinition>
     */
    public function availableProviders(): array;

    /**
     * Turns raw request input into a validated selection, or null when the
     * request expressed no preference and the configured default applies.
     *
     * @throws UnsupportedModelError when the provider/model is not allowlisted
     */
    public function resolveSelection(
        ?string $providerId,
        ?string $modelId,
        ?string $userApiKey = null,
    ): ?ModelSelection;
}
