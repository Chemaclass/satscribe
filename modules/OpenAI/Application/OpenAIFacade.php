<?php

declare(strict_types=1);

namespace Modules\OpenAI\Application;

use App\Models\Chat;
use Generator;
use Modules\OpenAI\Domain\Data\AiProviderDefinition;
use Modules\OpenAI\Domain\Data\ModelSelection;
use Modules\OpenAI\Domain\OpenAIFacadeInterface;
use Modules\OpenAI\Domain\ProviderRegistryInterface;
use Modules\Shared\Domain\Data\Blockchain\BlockchainData;
use Modules\Shared\Domain\Data\Chat\PromptInput;
use Modules\Shared\Domain\Enum\Chat\PromptPersona;

use function trim;

final readonly class OpenAIFacade implements OpenAIFacadeInterface
{
    public function __construct(
        private OpenAIService $openAIService,
        private ProviderRegistryInterface $registry,
    ) {
    }

    public function generateText(
        BlockchainData $data,
        PromptInput $input,
        PromptPersona $persona,
        string $question,
        ?Chat $chat = null,
        string $additionalContext = '',
        ?ModelSelection $selection = null,
    ): string {
        return $this->openAIService->generateText(
            $data,
            $input,
            $persona,
            $question,
            $chat,
            $additionalContext,
            $selection,
        );
    }

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
    ): Generator {
        return $this->openAIService->generateTextStreaming(
            $data,
            $input,
            $persona,
            $question,
            $chat,
            $additionalContext,
            $selection,
        );
    }

    /**
     * @return list<AiProviderDefinition>
     */
    public function availableProviders(): array
    {
        return $this->registry->all();
    }

    public function resolveSelection(
        ?string $providerId,
        ?string $modelId,
        ?string $userApiKey = null,
    ): ?ModelSelection {
        $expressedPreference = trim($providerId ?? '') !== ''
            || trim($modelId ?? '') !== ''
            || trim($userApiKey ?? '') !== '';

        if (!$expressedPreference) {
            return null;
        }

        return $this->registry->selectionFrom($providerId, $modelId, $userApiKey);
    }
}
