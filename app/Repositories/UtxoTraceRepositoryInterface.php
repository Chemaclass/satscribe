<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\UtxoTrace;

interface UtxoTraceRepositoryInterface
{
    public function find(string $txid, int $depth): ?UtxoTrace;

    public function store(string $txid, int $depth, array $result): UtxoTrace;
}
