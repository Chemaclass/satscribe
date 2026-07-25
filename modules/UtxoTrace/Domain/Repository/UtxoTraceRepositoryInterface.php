<?php

declare(strict_types=1);

namespace Modules\UtxoTrace\Domain\Repository;

use App\Models\UtxoTrace;
use Modules\UtxoTrace\Domain\UtxoTraceFacadeInterface;

/**
 * @phpstan-import-type TReferencedTrace from UtxoTraceFacadeInterface
 */
interface UtxoTraceRepositoryInterface
{
    public function find(string $txid, int $depth): ?UtxoTrace;

    /**
     * @param  TReferencedTrace  $result
     */
    public function store(string $txid, int $depth, array $result): UtxoTrace;
}
