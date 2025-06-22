<?php
declare(strict_types=1);

namespace Modules\OpenAI\Application;

use App\Models\Chat;
use Modules\OpenAI\Domain\OpenAIFacadeInterface;
use Modules\Shared\Domain\Data\Blockchain\BlockchainData;
use Modules\Shared\Domain\Data\Chat\PromptInput;
use Modules\Shared\Domain\Enum\Chat\PromptPersona;

final readonly class OpenAIFacade implements OpenAIFacadeInterface
{
    public function __construct(
        private OpenAIService $openAIService,
    ) {
    }

    public function generateText(
        BlockchainData $data,
        PromptInput $input,
        PromptPersona $persona,
        string $question,
        ?Chat $chat = null,
        string $additionalContext = '',
    ): string {
        return $this->openAIService->generateText($data, $input, $persona, $question, $chat, $additionalContext);
    }
}
