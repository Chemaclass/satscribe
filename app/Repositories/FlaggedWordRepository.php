<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\FlaggedWord;

final class FlaggedWordRepository
{
    /**
     * @return list<string>
     */
    public function getAllWords(): array
    {
        return FlaggedWord::where('is_active', true)
            ->pluck('word')
            ->all();
    }
}
