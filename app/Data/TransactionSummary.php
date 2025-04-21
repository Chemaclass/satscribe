<?php

declare(strict_types=1);

namespace App\Data;

final class TransactionSummary
{
    public function __construct(
        public string $txid,
        public bool $isConfirmed,
        public ?int $blockHeight,
        public ?int $blockTimestamp,
        public ?string $miner,
        public ?int $blockTxCount,
        public int $fee,
        public int $inputCount,
        public int $outputCount,
        public int $totalInput,
        public int $totalOutput,
        public bool $hasOpReturn,
        public bool $hasMultiSig,
        public bool $isTopFeePayer,
    ) {}

    public static function from(TransactionData $tx, ?BlockSummary $block = null): self
    {
        $inputs = collect($tx->vin);
        $outputs = collect($tx->vout);

        $totalInput = $inputs->sum(fn($vin) => $vin['prevout']['value'] ?? 0);
        $totalOutput = $outputs->sum('value');

        $hasOpReturn = $outputs->contains(fn($out) => $out['scriptpubkey_type'] === 'op_return');
        $hasMultiSig = $outputs->contains(fn($out) => $out['scriptpubkey_type'] === 'multisig');

        $isTopFeePayer = false;
        if ($block !== null) {
            $isTopFeePayer = collect($block->topTransactionsByFee)
                ->pluck('txid')
                ->contains($tx->txid);
        }

        return new self(
            txid: $tx->txid,
            isConfirmed: $tx->confirmed,
            blockHeight: $tx->blockHeight,
            blockTimestamp: $block?->timestamp,
            miner: $block?->miner,
            blockTxCount: $block?->txCount,
            fee: $tx->fee,
            inputCount: count($tx->vin),
            outputCount: count($tx->vout),
            totalInput: $totalInput,
            totalOutput: $totalOutput,
            hasOpReturn: $hasOpReturn,
            hasMultiSig: $hasMultiSig,
            isTopFeePayer: $isTopFeePayer,
        );
    }

    public function toPrompt(): string
    {
        $confirmedText = $this->isConfirmed ? 'Yes' : 'No';
        $block = $this->blockHeight !== null ? "#{$this->blockHeight}" : 'Unconfirmed';
        $opReturn = $this->hasOpReturn ? 'Yes' : 'No';
        $multisig = $this->hasMultiSig ? 'Yes' : 'No';
        $topFee = $this->isTopFeePayer ? 'Yes' : 'No';
        $miner = $this->miner ?? 'Unknown';
        $timestamp = $this->blockTimestamp ? date('Y-m-d H:i:s', $this->blockTimestamp) : '—';
        $txs = $this->blockTxCount ?? '—';

        return <<<TEXT
Transaction Summary
-------------------

- TXID: {$this->txid}
- Confirmed: {$confirmedText}
- Block: {$block}
- Block Timestamp: {$timestamp}
- Miner: {$miner}
- Total TXs in Block: {$txs}
- Fee: {$this->fee} sats
- Inputs: {$this->inputCount}
- Outputs: {$this->outputCount}
- Total Input: {$this->totalInput} sats
- Total Output: {$this->totalOutput} sats
- OP_RETURN present: {$opReturn}
- MultiSig Output: {$multisig}
- Among top fee payers: {$topFee}
TEXT;
    }
}
