<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\GeneratedPrompt;
use App\Data\PromptInput;
use App\Enums\PromptPersona;
use App\Models\SatscribeDescription;
use App\Repositories\SatscribeDescriptionRepository;
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
        private SatscribeDescriptionRepository $repository,
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
            $existing = $this->repository->findByCriteria($input, $persona, $question);

            if ($existing instanceof SatscribeDescription && !$existing->force_refresh) {
                return new GeneratedPrompt($existing, isFresh: false);
            }
        }

        $result = $this->generateFreshPrompt($input, $persona, $question);

        return new GeneratedPrompt($result, isFresh: true);
    }

    private function generateFreshPrompt(
        PromptInput $input,
        PromptPersona $persona,
        string $question = '',
    ): SatscribeDescription {
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
