<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Blockchain\BlockchainData;
use App\Data\PromptInput;
use App\Enums\PromptPersona;
use App\Models\Conversation;
use App\Repositories\ConversationRepository;
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
        private ConversationRepository $repository,
        private UserInputSanitizer $userInputSanitizer,
        private string $ip,
        private int $maxOpenAIAttempts,
    ) {
    }

    public function execute(
        Conversation $conversation,
        string $message,
    ): void {
        $this->enforceRateLimit();
        $firstUserMessage = $conversation->getFirstUserMessage();

        $cleanMsg = $this->userInputSanitizer->sanitize($message);

        $blockchainData = BlockchainData::fromMessage($conversation->getFirstAssistantMessage());

        $aiResponse = $this->openai->generateText(
            $blockchainData,
            PromptInput::fromRaw($firstUserMessage->input),
            PromptPersona::from($firstUserMessage->persona),
            $cleanMsg
        );

        $this->repository->addMessageToConversation($conversation, $cleanMsg, $aiResponse);
    }

    private function enforceRateLimit(): void
    {
        $key = "openai:{$this->ip}";

        if (RateLimiter::tooManyAttempts($key, $this->maxOpenAIAttempts)) {
            throw new ThrottleRequestsException(
                "You have reached the daily OpenAI limit of {$this->maxOpenAIAttempts} requests."
            );
        }

        RateLimiter::hit($key, self::RATE_LIMIT_SECONDS);
    }
}
