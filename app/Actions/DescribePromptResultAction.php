<?php
declare(strict_types=1);

namespace App\Actions;

use App\Repositories\PromptResultRepository;
use App\Services\BlockchainService;
use App\Services\OpenAIService;

final class DescribePromptResultAction
{
    public function __construct(
        private readonly BlockchainService $blockchain,
        private readonly OpenAIService $openai,
        private readonly PromptResultRepository $repository
    ) {
    }

    /**
     * @return array{description: string, data: array}
     */
    public function execute(string $input): ?array
    {
        $type = is_numeric($input) ? 'block' : 'transaction';

        // Check for cached result
        $existing = $this->repository->findByTypeAndInput($type, $input);
        if ($existing) {
            return [
                'description' => $existing->ai_response,
                'data' => $existing->raw_data,
            ];
        }

        // Fetch blockchain data
        $data = $this->blockchain->getData($input);
        if (!$data) {
            return null;
        }

        // Generate AI description
        $text = $this->openai->generateText($data, $type);

        // Save result
        $this->repository->save($type, $input, $text, $data);

        return [
            'description' => $text,
            'data' => $data->toArray(),
        ];
    }
}
