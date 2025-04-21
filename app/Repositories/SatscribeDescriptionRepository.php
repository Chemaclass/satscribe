<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Data\BlockchainData;
use App\Data\PromptInput;
use App\Enums\PromptPersona;
use App\Enums\PromptType;
use App\Models\SatscribeDescription;

final class SatscribeDescriptionRepository
{
    public function findByCriteria(
        PromptInput $input,
        ?string $question = null,
        ?PromptPersona $persona = null
    ): ?SatscribeDescription {
        return SatscribeDescription::query()
            ->where('type', $input->type->value)
            ->where('input', $input->text)
            ->when($question, fn($q) => $q->where('question', $question))
            ->when($persona, fn($q) => $q->where('persona', $persona))
            ->first();
    }

    public function deleteByTypeAndInput(PromptInput $input): void
    {
        SatscribeDescription::where('type', $input->type->value)
            ->where('input', $input->text)
            ->delete();
    }

    public function save(
        PromptInput $input,
        string $aiResponse,
        BlockchainData $data,
        PromptPersona $persona,
        ?string $question = null
    ): SatscribeDescription {
        $raw = $data->toArray();

        $forceRefresh = $input->type->value === PromptType::Transaction->value
            && isset($raw['status']['confirmed'])
            && $raw['status']['confirmed'] === false;

        return SatscribeDescription::create([
            'type' => $input->type->value,
            'input' => $input->text,
            'question' => $question,
            'ai_response' => $aiResponse,
            'raw_data' => $raw,
            'force_refresh' => $forceRefresh,
            'persona' => $persona->value,
        ]);
    }
}
