<?php

declare(strict_types=1);

namespace Modules\UtxoTrace\Application;

use Modules\Shared\Domain\Data\Blockchain\TransactionData;
use Modules\UtxoTrace\Application\Tracer\TransactionTracer;
use Modules\UtxoTrace\Application\Tracer\UtxoTracer;
use Modules\UtxoTrace\Domain\UtxoTraceFacadeInterface;

/**
 * @phpstan-import-type TReferencedTrace from UtxoTraceFacadeInterface
 */
final readonly class UtxoTraceFacade implements UtxoTraceFacadeInterface
{
    public function __construct(
        private UtxoTracer $utxoTracer,
        private TransactionTracer $transactionTracer,
    ) {
    }

    /**
     * @return TReferencedTrace
     */
    public function getUtxoBacktrace(string $txid, int $depth = 1): array
    {
        return $this->utxoTracer->getBacktrace($txid, $depth);
    }

    /**
     * @return list<TransactionData>
     */
    public function getTransactionBacktrace(string $txid, int $depth = 10): array
    {
        return $this->transactionTracer->getBacktrace($txid, $depth);
    }

    /**
     * @param  list<TransactionData>  $trace
     */
    public function formatForPrompt(array $trace): string
    {
        return $this->transactionTracer->formatForPrompt($trace);
    }
}
