<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\BlockchainData;
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

    public function execute(string $input, bool $refresh = false): ?PromptResult
    {
        $type = is_numeric($input) ? 'block' : 'transaction';

        if (!$refresh) {
            $cached = $this->repository->findByTypeAndInput($type, $input);
            if ($cached instanceof PromptResult) {
                return $cached;
            }
        }

        return $this->getFreshResult($input, $type, $refresh);
    }

    private function getFreshResult(string $input, string $type, bool $refresh): ?PromptResult
    {
        if ($refresh) {
            $this->repository->deleteByTypeAndInput($type, $input);
        }

        $data = $this->blockchain->getData($input);

        if (!$data instanceof BlockchainData) {
            return null;
        }

        $response = $this->openai->generateText($data, $type);

        return $this->repository->save($type, $input, $response, $data);
    }
}
