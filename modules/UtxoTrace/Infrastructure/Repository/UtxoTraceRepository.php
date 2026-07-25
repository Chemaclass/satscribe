<?php

declare(strict_types=1);

namespace Modules\UtxoTrace\Infrastructure\Repository;

use App\Models\UtxoTrace;
use Modules\UtxoTrace\Domain\Repository\UtxoTraceRepositoryInterface;
use Modules\UtxoTrace\Domain\UtxoTraceFacadeInterface;

/**
 * @phpstan-import-type TReferencedTrace from UtxoTraceFacadeInterface
 */
final readonly class UtxoTraceRepository implements UtxoTraceRepositoryInterface
{
    public function find(string $txid, int $depth): ?UtxoTrace
    {
        return UtxoTrace::where('txid', $txid)
            ->where('depth', $depth)
            ->first();
    }

    /**
     * @param  TReferencedTrace  $result
     */
    public function store(string $txid, int $depth, array $result): UtxoTrace
    {
        return UtxoTrace::updateOrCreate(
            ['txid' => $txid, 'depth' => $depth],
            ['result' => $result],
        );
    }
}
