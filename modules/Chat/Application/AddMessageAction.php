<?php

declare(strict_types=1);

namespace Modules\Chat\Application;

use App\Models\Chat;
use App\Models\Message;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Blockchain\Domain\BlockchainFacadeInterface;
use Modules\Chat\Domain\Data\UserInputSanitizer;
use Modules\Chat\Domain\Repository\ChatRepositoryInterface;
use Modules\Chat\Domain\Repository\MessageRepositoryInterface;
use Modules\OpenAI\Domain\OpenAIFacadeInterface;
use Modules\Shared\Domain\Data\Chat\PromptInput;
use Modules\Shared\Domain\Enum\Chat\PromptPersona;
use Psr\Log\LoggerInterface;

final readonly class AddMessageAction
{
    private const RATE_LIMIT_SECONDS = 86400; // 24 hours

    public function __construct(
        private BlockchainFacadeInterface $blockchainFacade,
        private OpenAIFacadeInterface $openAIFacade,
        private ChatRepositoryInterface $chatRepository,
        private MessageRepositoryInterface $messageRepository,
        private UserInputSanitizer $userInputSanitizer,
        private AdditionalContextBuilder $contextBuilder,
        private LoggerInterface $logger,
        private string $trackingId = '',
        private int $maxOpenAIAttempts = 1000,
    ) {
    }

    public function execute(Chat $chat, string $message): void
    {
        $this->logger->info('Adding message to chat', ['chat_id' => $chat->id]);
        $this->enforceRateLimit();
        $firstUserMessage = $chat->getFirstUserMessage();

        $input = PromptInput::fromRaw($firstUserMessage->input);
        $cleanMsg = $this->userInputSanitizer->sanitize($message);
        $persona = PromptPersona::from($firstUserMessage->persona);

        $aiResponse = $this->generateAiResponse($input, $persona, $cleanMsg, $chat);

        $this->chatRepository->addMessageToChat($chat, $cleanMsg, $aiResponse);
        $this->logger->info('Message added to chat', ['chat_id' => $chat->id]);
    }

    private function enforceRateLimit(): void
    {
        $key = "openai:{$this->trackingId}";

        if (RateLimiter::tooManyAttempts($key, $this->maxOpenAIAttempts)) {
            throw new ThrottleRequestsException(
                "You have reached the daily OpenAI limit of {$this->maxOpenAIAttempts} requests.",
            );
        }

        RateLimiter::hit($key, self::RATE_LIMIT_SECONDS);
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
