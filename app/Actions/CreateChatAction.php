<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\GeneratedPrompt;
use App\Data\PromptInput;
use App\Enums\PromptPersona;
use App\Models\Chat;
use App\Repositories\ChatRepository;
use App\Repositories\MessageRepository;
use App\Services\BlockchainService;
use App\Services\OpenAIService;
use App\Services\UserInputSanitizer;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\RateLimiter;

final readonly class CreateChatAction
{
    private const RATE_LIMIT_SECONDS = 86400; // 24 hours

    public function __construct(
        private BlockchainService $blockchain,
        private OpenAIService $openai,
        private ChatRepository $repository,
        private MessageRepository $messageRepository,
        private UserInputSanitizer $userInputSanitizer,
        private string $ip,
        private int $maxOpenAIAttempts,
    ) {
    }

    public function execute(
        PromptInput $input,
        PromptPersona $persona,
        string $question,
        bool $refreshEnabled = false,
    ): GeneratedPrompt {
        // Return a cached result unless forced to refresh
        if (!$refreshEnabled) {
            $chat = $this->repository->findByCriteria($input, $persona, $question);

            if ($chat instanceof Chat && !$chat->force_refresh) {
                return new GeneratedPrompt($chat, isFresh: false);
            }
        }

        $result = $this->createNewChat($input, $persona, $question, $refreshEnabled);

        return new GeneratedPrompt($result, isFresh: true);
    }

    private function createNewChat(
        PromptInput $input,
        PromptPersona $persona,
        string $question,
        bool $refreshEnabled,
    ): Chat {
        $this->enforceRateLimit();

        $blockchainData = $this->blockchain->getBlockchainData($input);
        $cleanQuestion = $this->userInputSanitizer->sanitize($question);

        if ($refreshEnabled) {
            $aiResponse = $this->openai->generateText($blockchainData, $input, $persona, $cleanQuestion);
        } else {
            $aiResponse = $this->messageRepository->findAiResponse($input, $persona, $question);
        }


        return $this->repository->createChat($input, $aiResponse, $blockchainData->current(), $persona, $cleanQuestion);
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
