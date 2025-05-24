<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Message;
use App\Data\PromptInput;
use App\Enums\PromptPersona;
use App\Models\Chat;
use App\Repositories\ChatRepositoryInterface;
use App\Repositories\MessageRepositoryInterface;
use App\Services\BlockchainService;
use App\Services\OpenAIService;
use App\Services\UserInputSanitizer;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\RateLimiter;

final readonly class AddMessageAction
{
    private const RATE_LIMIT_SECONDS = 86400; // 24 hours

    public function __construct(
        private BlockchainService $blockchain,
        private OpenAIService $openai,
        private ChatRepositoryInterface $chatRepository,
        private MessageRepositoryInterface $messageRepository,
        private UserInputSanitizer $userInputSanitizer,
        private string $trackingId,
        private int $maxOpenAIAttempts,
    ) {
    }

    public function execute(
        Chat $chat,
        string $message,
    ): void {
        $this->enforceRateLimit();
        $firstUserMessage = $chat->getFirstUserMessage();

        $input = PromptInput::fromRaw($firstUserMessage->input);
        $cleanMsg = $this->userInputSanitizer->sanitize($message);
        $persona = PromptPersona::from($firstUserMessage->persona);

        $aiResponse = $this->generateAiResponse($input, $persona, $cleanMsg, $chat);

        $this->chatRepository->addMessageToChat($chat, $cleanMsg, $aiResponse);
    }

    private function enforceRateLimit(): void
    {
        $key = "openai:{$this->trackingId}";

        if (RateLimiter::tooManyAttempts($key, $this->maxOpenAIAttempts)) {
            throw new ThrottleRequestsException(
                "You have reached the daily OpenAI limit of {$this->maxOpenAIAttempts} requests."
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

        return $this->openai->generateText(
            $this->blockchain->getBlockchainData($input),
            $input,
            $persona,
            $cleanMsg,
            $chat
        );
    }
}
