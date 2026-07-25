<?php

declare(strict_types=1);

namespace Modules\UtxoTrace\Domain;

use Modules\Shared\Domain\Data\Blockchain\TransactionData;

/**
 * `references` maps a reference id (`r1`, `r2`, ...) to a deduplicated trace
 * node; each `utxos[].trace` entry is one of those ids.
 *
 * @phpstan-type TReferencedTrace array{
 *     utxos: list<array{utxo: array<string, mixed>, trace: list<string>}>,
 *     references: array<string, array<string, mixed>>,
 * }
 */
interface UtxoTraceFacadeInterface
{
    /**
     * The defaults below are the ones callers actually get: PHP takes them from
     * the implementation, so an interface default that disagrees is a lie the
     * type checker never catches.
     *
     * @return TReferencedTrace
     */
    public function getUtxoBacktrace(string $txid, int $depth = 1): array;

    /**
     * @param int $depth maximum number of ancestor transactions to walk
     *
     * @return list<TransactionData> ordered from the given tx to its ancestors
     */
    public function getTransactionBacktrace(string $txid, int $depth = 10): array;

    /**
     * @param  list<TransactionData>  $trace
     */
    public function formatForPrompt(array $trace): string;
}
