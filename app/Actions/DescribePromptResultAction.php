<?php
declare(strict_types=1);

namespace App\Actions;

use App\Data\BlockchainData;
use App\Models\PromptResult;
use App\Repositories\PromptResultRepository;
use App\Services\BlockchainService;
use App\Services\OpenAIService;

final readonly class DescribePromptResultAction
{
    public function __construct(
        private BlockchainService $blockchain,
        private OpenAIService $openai,
        private PromptResultRepository $repository
    ) {
    }

    public function execute(string $input): ?PromptResult
    {
        $type = is_numeric($input) ? 'block' : 'transaction';

        $existing = $this->repository->findByTypeAndInput($type, $input);
        if ($existing instanceof PromptResult) {
            return $existing;
        }

        $data = $this->blockchain->getData($input);
        if (!$data instanceof BlockchainData) {
            return null;
        }

        $text = $this->openai->generateText($data, $type);

        return $this->repository->save($type, $input, $text, $data);
    }
}
