<?php

declare(strict_types=1);

namespace Modules\Chat\Domain\Repository;

interface FlaggedWordRepositoryInterface
{
    /**
     * @return list<string>
     */
    public function getAllWords(): array;
}
