<?php

declare(strict_types=1);

namespace Modules\UtxoTrace\Application;

use Modules\UtxoTrace\Application\Tracer\TransactionTracer;
use Modules\UtxoTrace\Application\Tracer\UtxoTracer;
use Modules\UtxoTrace\Domain\UtxoTraceFacadeInterface;

final readonly class UtxoTraceFacade implements UtxoTraceFacadeInterface
{
    public function __construct(
        private UtxoTracer $utxoTracer,
        private TransactionTracer $transactionTracer,
    ) {
    }

    /**
     * not used(?)
     */
    public function getUtxoBacktrace(string $txid, int $depth = 1): array
    {
        return $this->utxoTracer->getBacktrace($txid, $depth);
    }

    public function getTransactionBacktrace(string $txid, int $depth = 10): array
    {
        return $this->transactionTracer->getBacktrace($txid);
    }

    public function formatForPrompt(array $trace): string
    {
        return $this->transactionTracer->formatForPrompt($trace);
    }
}
