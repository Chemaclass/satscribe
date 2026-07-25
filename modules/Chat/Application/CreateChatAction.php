<?php

declare(strict_types=1);

namespace Modules\Chat\Application;

use App\Models\Chat;
use App\Models\Message;
use Modules\Blockchain\Domain\BlockchainFacadeInterface;
use Modules\Chat\Domain\CreateChatActionInterface;
use Modules\Chat\Domain\Data\CreateChatActionResult;
use Modules\Chat\Domain\Data\UserInputSanitizer;
use Modules\Chat\Domain\Repository\ChatRepositoryInterface;
use Modules\Chat\Domain\Repository\MessageRepositoryInterface;
use Modules\OpenAI\Domain\OpenAIFacadeInterface;
use Modules\Shared\Domain\Data\Blockchain\BlockchainData;
use Modules\Shared\Domain\Data\Chat\PromptInput;
use Modules\Shared\Domain\Enum\Chat\PromptPersona;
use Psr\Log\LoggerInterface;

final readonly class CreateChatAction implements CreateChatActionInterface
{
    public function __construct(
        private BlockchainFacadeInterface $blockchainFacade,
        private OpenAIFacadeInterface $openaiFacade,
        private ChatRepositoryInterface $repository,
        private MessageRepositoryInterface $messageRepository,
        private UserInputSanitizer $userInputSanitizer,
        private AdditionalContextBuilder $contextBuilder,
        private OpenAiRateLimiter $rateLimiter,
        private LoggerInterface $logger,
    ) {
    }

    public function execute(
        PromptInput $input,
        PromptPersona $persona,
        string $question,
        bool $refreshEnabled = false,
        bool $isPublic = false,
    ): CreateChatActionResult {
        $this->logger->debug('Create chat action started', [
            'input' => $input->text,
            'persona' => $persona->value,
            'refresh' => $refreshEnabled,
        ]);

        if (!$refreshEnabled) {
            $chat = $this->repository->findByCriteria($input, $persona, $question);

            if ($chat instanceof Chat && !$chat->force_refresh) {
                $this->logger->debug('Returning cached chat', ['chat_id' => $chat->id]);
                return new CreateChatActionResult($chat, isFresh: false);
            }
        }

        $result = $this->createNewChat($input, $persona, $question, $refreshEnabled, $isPublic);
        $this->logger->info('New chat created', ['chat_id' => $result->id]);

        return new CreateChatActionResult($result, isFresh: true);
    }

    private function createNewChat(
        PromptInput $input,
        PromptPersona $persona,
        string $question,
        bool $refreshEnabled,
        bool $isPublic,
    ): Chat {
        $this->rateLimiter->enforce();

        $blockchainData = $this->blockchainFacade->getBlockchainData($input);
        $cleanQuestion = $this->userInputSanitizer->sanitize($question);

        $aiResponse = $refreshEnabled
            ? $this->generateAiResponse($blockchainData, $input, $persona, $cleanQuestion)
            : $this->findOrGenerateAiResponse($input, $persona, $question, $blockchainData, $cleanQuestion);

        return $this->repository->createChat(
            $input,
            $aiResponse,
            $blockchainData->current(),
            $persona,
            $cleanQuestion,
            $isPublic,
        );
    }

    private function generateAiResponse(
        BlockchainData $blockchainData,
        PromptInput $input,
        PromptPersona $persona,
        string $cleanQuestion,
    ): string {
        $additional = $this->contextBuilder->build($blockchainData, $input, $cleanQuestion);

        return $this->openaiFacade->generateText($blockchainData, $input, $persona, $cleanQuestion, null, $additional);
    }

    private function findOrGenerateAiResponse(
        PromptInput $input,
        PromptPersona $persona,
        string $question,
        BlockchainData $blockchainData,
        string $cleanQuestion,
    ): string {
        $message = $this->messageRepository->findAssistantMessage($input, $persona, $question);
        if ($message instanceof Message) {
            return $message->content;
        }

        return $this->generateAiResponse($blockchainData, $input, $persona, $cleanQuestion);
    }
}
