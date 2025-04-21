<?php

declare(strict_types=1);

namespace App\Data;

final class TransactionSummary
{
    public function __construct(
        public string $txid,
        public bool $isConfirmed,
        public ?int $blockHeight,
        public int $fee,
        public int $inputCount,
        public int $outputCount,
        public int $totalInput,
        public int $totalOutput,
        public bool $hasOpReturn,
        public bool $hasMultiSig,
    ) {
    }

    public static function from(TransactionData $data): self
    {
        $inputs = collect($data->vin);
        $outputs = collect($data->vout);

        $totalInput = $inputs->sum(fn($vin) => $vin['prevout']['value'] ?? 0);
        $totalOutput = $outputs->sum('value');

        $hasOpReturn = $outputs->contains(fn($out) => $out['scriptpubkey_type'] === 'op_return');

        $hasMultiSig = $outputs->contains(fn($out) => $out['scriptpubkey_type'] === 'multisig');

        return new self(
            txid: $data->txid,
            isConfirmed: $data->confirmed,
            blockHeight: $data->blockHeight,
            fee: $data->fee,
            inputCount: count($data->vin),
            outputCount: count($data->vout),
            totalInput: $totalInput,
            totalOutput: $totalOutput,
            hasOpReturn: $hasOpReturn,
            hasMultiSig: $hasMultiSig,
        );
    }

    public function toPrompt(): string
    {
        $confirmedText = $this->isConfirmed ? 'Yes' : 'No';
        $block = $this->blockHeight !== null ? $this->blockHeight : 'Unconfirmed';
        $opReturn = $this->hasOpReturn ? 'Yes' : 'No';
        $multisig = $this->hasMultiSig ? 'Yes' : 'No';

        return <<<TEXT
Transaction Summary
-------------------

- TXID: {$this->txid}
- Confirmed: {$confirmedText}
- Block Height: {$block}
- Fee: {$this->fee} sats
- Inputs: {$this->inputCount}
- Outputs: {$this->outputCount}
- Total Input: {$this->totalInput} sats
- Total Output: {$this->totalOutput} sats
- OP_RETURN present: {$opReturn}
- MultiSig Output: {$multisig}
TEXT;
    }
}
