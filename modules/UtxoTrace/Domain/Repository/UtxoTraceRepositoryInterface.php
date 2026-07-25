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
    /**
     * Bump whenever the tracer's output shape or semantics change. Stored rows
     * carrying a different version are ignored and recomputed, which is what
     * keeps a permanent cache from serving results an older algorithm produced.
     */
    public const CURRENT_VERSION = 1;

    public function find(string $txid, int $depth): ?UtxoTrace;

    /**
     * @param  TReferencedTrace  $result
     */
    public function store(string $txid, int $depth, array $result): UtxoTrace;
}
