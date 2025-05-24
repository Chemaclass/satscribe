<?php

declare(strict_types=1);

namespace App\Repositories;

interface FlaggedWordRepositoryInterface
{
    /**
     * @return list<string>
     */
    public function getAllWords(): array;
}
