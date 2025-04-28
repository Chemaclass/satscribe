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
        if (!$refreshEnabled) {
            $cached = $this->repository->findByCriteria($input, $persona, $question);

            if ($cached instanceof SatscribeDescription && !$cached->force_refresh) {
                return new GeneratedPrompt($cached, isFresh: false);
            }
        }

        $fresh = $this->getFreshResult($input, $persona, $refreshEnabled, $question);

        return new GeneratedPrompt($fresh, isFresh: true);
    }

    private function getFreshResult(
        PromptInput $input,
        PromptPersona $persona,
        bool $refresh,
        string $question = '',
    ): SatscribeDescription {
        $this->checkRateLimiter();

        if ($refresh) {
            // @todo: do not delete; instead save a new item, and when looking for the cached one, always fetch latest
            $this->repository->deleteByTypeAndInput($input);
        }

        $blockchainData = $this->blockchain->getBlockchainData($input);
        $question = $this->userInputSanitizer->sanitize($question);
        $aiResponse = $this->openai->generateText($blockchainData, $input, $persona, $question);

        return $this->repository->save($input, $aiResponse, $blockchainData->current(), $persona, $question);
    }

    private function checkRateLimiter(): void
    {
        $key = 'openai:'.$this->ip;
        if (!RateLimiter::remaining($key, $this->maxOpenAIAttempts)) {
            throw new ThrottleRequestsException('You have reached the daily OpenAI limit.');
        }

        RateLimiter::hit($key, 60 * 60 * 24); // one day
    }
}
