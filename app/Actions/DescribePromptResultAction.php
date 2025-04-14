<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\BlockchainData;
use App\Data\DescribedPrompt;
use App\Models\PromptResult;
use App\Repositories\PromptResultRepository;
use App\Services\BlockchainService;
use App\Services\OpenAIService;
use function is_numeric;

final readonly class DescribePromptResultAction
{
    public function __construct(
        private BlockchainService $blockchain,
        private OpenAIService $openai,
        private PromptResultRepository $repository,
    ) {
    }

    public function execute(string $input, bool $refresh = false): ?DescribedPrompt
    {
        $type = is_numeric($input) ? 'block' : 'transaction';

        if (!$refresh) {
            $cached = $this->repository->findByTypeAndInput($type, $input);
            if ($cached instanceof PromptResult && !$cached->force_refresh) {
                return new DescribedPrompt($cached, isFresh: false);
            }
        }

        $fresh = $this->getFreshResult($input, $type, $refresh);

        return $fresh instanceof PromptResult ? new DescribedPrompt($fresh, isFresh: true) : null;
    }

    private function getFreshResult(string $input, string $type, bool $refresh): ?PromptResult
    {
        if ($refresh) {
            $this->repository->deleteByTypeAndInput($type, $input);
        }

        $data = $this->blockchain->getBlockchainData($input);
        if (!$data instanceof BlockchainData) {
            return null;
        }

        $response = $this->openai->generateText($data, $type);
        if ($response === null) {
            return null;
        }

        return $this->repository->save($type, $input, $response, $data);
    }
}
