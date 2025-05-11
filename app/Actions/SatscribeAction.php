<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\GeneratedPrompt;
use App\Data\PromptInput;
use App\Enums\PromptPersona;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\SatscribeDescription;
use App\Repositories\ConversationRepository;
use App\Services\BlockchainService;
use App\Services\OpenAIService;
use App\Services\UserInputSanitizer;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\RateLimiter;

final readonly class SatscribeAction
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
        PromptInput $input,
        PromptPersona $persona,
        bool $refreshEnabled = false,
        string $question = '',
    ): GeneratedPrompt {
        // Return a cached result unless forced to refresh
        if (!$refreshEnabled) {
            $conversation = $this->repository->findByCriteria($input, $persona, $question);

            if ($conversation instanceof Conversation && !$conversation->force_refresh) {
                return new GeneratedPrompt($conversation, isFresh: false);
            }
        }

        $result = $this->generateFreshConversation($input, $persona, $question);

        return new GeneratedPrompt($result, isFresh: true);
    }

    private function generateFreshConversation(
        PromptInput $input,
        PromptPersona $persona,
        string $question = '',
    ): Conversation {
        $this->enforceRateLimit();

        $blockchainData = $this->blockchain->getBlockchainData($input);
        $cleanQuestion = $this->userInputSanitizer->sanitize($question);
        $aiResponse = $this->openai->generateText($blockchainData, $input, $persona, $cleanQuestion);

        return $this->repository->save($input, $aiResponse, $blockchainData->current(), $persona, $cleanQuestion);
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
