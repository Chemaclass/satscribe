<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Data\BlockchainData;
use App\Models\PromptResult;

final class PromptResultRepository
{
    public function findByTypeAndInput(string $type, string $input): ?PromptResult
    {
        return PromptResult::where('type', $type)
            ->where('input', $input)
            ->first();
    }

    public function deleteByTypeAndInput(string $type, string $input): void
    {
        PromptResult::where('type', $type)
            ->where('input', $input)
            ->delete();
    }

    public function save(string $type, string $input, string $aiResponse, BlockchainData $data): PromptResult
    {
        return PromptResult::create([
            'type' => $type,
            'input' => $input,
            'ai_response' => $aiResponse,
            'raw_data' => $data->toArray(),
        ]);
    }
}
