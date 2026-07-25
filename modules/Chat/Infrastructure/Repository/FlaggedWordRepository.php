<?php

declare(strict_types=1);

namespace Modules\Chat\Infrastructure\Repository;

use App\Models\FlaggedWord;
use Modules\Chat\Domain\Repository\FlaggedWordRepositoryInterface;

final class FlaggedWordRepository implements FlaggedWordRepositoryInterface
{
    /**
     * @return list<string>
     */
    public function getAllWords(): array
    {
        return array_values(
            FlaggedWord::where('is_active', true)
                ->pluck('word')
                ->map(static fn ($word): string => as_string($word))
                ->all(),
        );
    }
}
