<?php

declare(strict_types=1);

namespace Modules\Chat\Application;

use App\Models\Chat;
use App\Models\Message;
use Modules\Blockchain\Domain\BlockchainFacadeInterface;
use Modules\Chat\Domain\AddMessageActionInterface;
use Modules\Chat\Domain\Data\UserInputSanitizer;
use Modules\Chat\Domain\Repository\ChatRepositoryInterface;
use Modules\Chat\Domain\Repository\MessageRepositoryInterface;
use Modules\OpenAI\Domain\OpenAIFacadeInterface;
use Modules\Shared\Domain\Data\Chat\PromptInput;
use Modules\Shared\Domain\Enum\Chat\PromptPersona;
use Psr\Log\LoggerInterface;

final readonly class AddMessageAction implements AddMessageActionInterface
{
    public function __construct(
        private BlockchainFacadeInterface $blockchainFacade,
        private OpenAIFacadeInterface $openAIFacade,
        private ChatRepositoryInterface $chatRepository,
        private MessageRepositoryInterface $messageRepository,
        private UserInputSanitizer $userInputSanitizer,
        private AdditionalContextBuilder $contextBuilder,
        private OpenAiRateLimiter $rateLimiter,
        private LoggerInterface $logger,
    ) {
    }

    public function execute(Chat $chat, string $message): void
    {
        $this->logger->debug('Adding message to chat', ['chat_id' => $chat->id]);
        $this->rateLimiter->enforce();
        $firstUserMessage = $chat->getFirstUserMessage();

        $input = PromptInput::fromRaw($firstUserMessage->input);
        $cleanMsg = $this->userInputSanitizer->sanitize($message);
        $persona = PromptPersona::from($firstUserMessage->persona);

        $aiResponse = $this->generateAiResponse($input, $persona, $cleanMsg, $chat);

        $this->chatRepository->addMessageToChat($chat, $cleanMsg, $aiResponse);
        $this->logger->debug('Message added to chat', ['chat_id' => $chat->id]);
    }

    private function generateAiResponse(
        PromptInput $input,
        PromptPersona $persona,
        string $cleanMsg,
        Chat $chat,
    ): string {
        $message = $this->messageRepository->findAssistantMessage($input, $persona, $cleanMsg);
        if ($message instanceof Message) {
            return $message->content;
        }

        $data = $this->blockchainFacade->getBlockchainData($input);
        $additional = $this->contextBuilder->build($data, $input, $cleanMsg);

        return $this->openAIFacade->generateText(
            $data,
            $input,
            $persona,
            $cleanMsg,
            $chat,
            $additional,
        );
    }
}
