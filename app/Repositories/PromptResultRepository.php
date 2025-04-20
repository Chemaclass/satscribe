<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Data\BlockchainData;
use App\Data\PromptInput;
use App\Models\PromptResult;

final class PromptResultRepository
{
    public function findByTypeAndInput(PromptInput $input, ?string $question = null): ?PromptResult
    {
        return PromptResult::where('type', $input->type->value)
            ->where('input', $input->text)
            ->when($question, fn($q) => $q->where('question', $question))
            ->first();
    }

    public function deleteByTypeAndInput(PromptInput $input): void
    {
        PromptResult::where('type', $input->type->value)
            ->where('input', $input->text)
            ->delete();
    }

    public function save(
        PromptInput $input,
        string $aiResponse,
        BlockchainData $data,
        ?string $question = null
    ): PromptResult {
        $raw = $data->toArray();

        $forceRefresh = $input->type->value === 'transaction'
            && isset($raw['status']['confirmed'])
            && $raw['status']['confirmed'] === false;

        return PromptResult::create([
            'type' => $input->type->value,
            'input' => $input->text,
            'question' => $question,
            'ai_response' => $aiResponse,
            'raw_data' => $raw,
            'force_refresh' => $forceRefresh,
        ]);
    }
}
