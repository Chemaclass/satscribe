<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\UtxoTrace;

final readonly class UtxoTraceRepository implements UtxoTraceRepositoryInterface
{
    public function find(string $txid, int $depth): ?UtxoTrace
    {
        return UtxoTrace::where('txid', $txid)
            ->where('depth', $depth)
            ->first();
    }

    public function store(string $txid, int $depth, array $result): UtxoTrace
    {
        return UtxoTrace::updateOrCreate(
            ['txid' => $txid, 'depth' => $depth],
            ['result' => $result]
        );
    }
}
