<?php

declare(strict_types=1);

namespace Modules\UtxoTrace\Domain;

interface UtxoTraceFacadeInterface
{
    public function getUtxoBacktrace(string $txid, int $depth = 2): array;

    public function getTransactionBacktrace(string $txid, int $depth = 2): array;

    public function formatForPrompt(array $trace): string;
}
