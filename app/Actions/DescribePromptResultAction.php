<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\BlockchainData;
use App\Data\GeneratedPrompt;
use App\Models\PromptResult;
use App\Repositories\PromptResultRepository;
use App\Services\BlockchainService;
use App\Services\OpenAIService;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\RateLimiter;
use function is_numeric;

final readonly class DescribePromptResultAction
{
    public function __construct(
        private BlockchainService $blockchain,
        private OpenAIService $openai,
        private PromptResultRepository $repository,
        private string $ip,
        private int $maxOpenAIAttempts,
    ) {
    }

    public function execute(string $input, bool $refresh = false, string $question = ''): ?GeneratedPrompt
    {
        $type = is_numeric($input) ? 'block' : 'transaction';

        if (!$refresh) {
            $cached = $this->repository->findByTypeAndInput($type, $input, $question);

            if ($cached instanceof PromptResult && !$cached->force_refresh) {
                return new GeneratedPrompt($cached, isFresh: false);
            }
        }

        $fresh = $this->getFreshResult($input, $type, $refresh, $question);

        return $fresh instanceof PromptResult ? new GeneratedPrompt($fresh, isFresh: true) : null;
    }

    private function getFreshResult(string $input, string $type, bool $refresh, string $question = ''): ?PromptResult
    {
        $this->checkRateLimiter();
        if ($refresh) {
            $this->repository->deleteByTypeAndInput($type, $input);
        }

        $data = $this->blockchain->getBlockchainData($input);
        if (!$data instanceof BlockchainData) {
            return null;
        }

        $response = $this->openai->generateText($data, $type, $question);
        if ($response === null) {
            return null;
        }

        return $this->repository->save($type, $input, $response, $data, $question);
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
