<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Data\BlockchainData;
use App\Models\PromptResult;

final class PromptResultRepository
{
    public function findByTypeAndInput(string $type, string $input, ?string $question = null): ?PromptResult
    {
        return PromptResult::where('type', $type)
            ->where('input', $input)
            ->when($question, fn ($q) => $q->where('question', $question))
            ->first();
    }

    public function deleteByTypeAndInput(string $type, string $input): void
    {
        PromptResult::where('type', $type)
            ->where('input', $input)
            ->delete();
    }

    public function save(
        string $type,
        string $input,
        string $aiResponse,
        BlockchainData $data,
        ?string $question = null
    ): PromptResult {
        $raw = $data->toArray();

        $forceRefresh = $type === 'transaction'
            && isset($raw['status']['confirmed'])
            && $raw['status']['confirmed'] === false;

        return PromptResult::create([
            'type' => $type,
            'input' => $input,
            'question' => $question,
            'ai_response' => $aiResponse,
            'raw_data' => $raw,
            'force_refresh' => $forceRefresh,
        ]);
    }
}
