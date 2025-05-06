<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Data\BlockchainDataInterface;
use App\Data\PromptInput;
use App\Enums\PromptPersona;
use App\Enums\PromptType;
use App\Models\SatscribeDescription;
use Illuminate\Pagination\Paginator;

final readonly class SatscribeDescriptionRepository
{
    public function __construct(
        private int $perPage
    ) {
    }

    public function findByCriteria(
        PromptInput $input,
        PromptPersona $persona,
        string $question = '',
    ): ?SatscribeDescription {
        return SatscribeDescription::query()
            ->where('type', $input->type->value)
            ->where('input', $input->text)
            ->where('persona', $persona->value)
            ->where('question', $question)
            ->first();
    }

    public function save(
        PromptInput $input,
        string $aiResponse,
        BlockchainDataInterface $blockchainData,
        PromptPersona $persona,
        string $question = ''
    ): SatscribeDescription {
        $raw = $blockchainData->toArray();

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
            'persona' => $persona,
        ]);
    }

    public function getPagination(): Paginator
    {
        return SatscribeDescription::latest()
            ->simplePaginate($this->perPage);
    }
}
