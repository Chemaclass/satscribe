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
        public array $walletTypes,
        public bool $isCoinJoinLike,
        public bool $isConsolidationLike,
    ) {
    }

    public static function from(TransactionData $tx, ?BlockSummary $block = null): self
    {
        $inputs = collect($tx->vin);
        $outputs = collect($tx->vout);

        $totalInput = $inputs->sum(fn($vin) => $vin['prevout']['value'] ?? 0);
        $totalOutput = $outputs->sum('value');

        $hasOpReturn = $outputs->contains(fn($out) => $out['scriptpubkey_type'] === 'op_return');
        $hasMultiSig = $outputs->contains(fn($out) => $out['scriptpubkey_type'] === 'multisig');

        $isTopFeePayer = $block !== null && collect($block->topTransactionsByFee)
                ->pluck('txid')->contains($tx->txid);

        $walletTypes = collect([...$inputs, ...$outputs])
            ->map(fn($io) => $io['prevout']['scriptpubkey_type'] ?? $io['scriptpubkey_type'] ?? null)
            ->filter()
            ->countBy()
            ->toArray();

        $uniqueInputAddresses = $inputs->map(fn($vin
        ) => $vin['prevout']['scriptpubkey_address'] ?? null)->filter()->unique();
        $outputValues = $outputs->pluck('value')->filter();

        $isCoinJoinLike = $uniqueInputAddresses->count() > 5
            && $outputValues->countBy()->max() > 2;

        $isConsolidationLike = $inputs->count() > 5 && $outputs->count() <= 2;

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
            walletTypes: $walletTypes,
            isCoinJoinLike: $isCoinJoinLike,
            isConsolidationLike: $isConsolidationLike,
        );
    }

    public function toPrompt(): string
    {
        $confirmedText = $this->isConfirmed ? 'Yes' : 'No';
        $block = $this->blockHeight !== null ? "#{$this->blockHeight}" : 'Unconfirmed';
        $timestamp = $this->blockTimestamp ? date('Y-m-d H:i:s', $this->blockTimestamp) : '—';
        $miner = $this->miner ?? 'Unknown';
        $opReturn = $this->hasOpReturn ? 'Yes' : 'No';
        $multisig = $this->hasMultiSig ? 'Yes' : 'No';
        $topFee = $this->isTopFeePayer ? 'Yes' : 'No';
        $coinjoin = $this->isCoinJoinLike ? 'Likely' : 'No';
        $consolidation = $this->isConsolidationLike ? 'Likely' : 'No';
        $blockTxCountText = $this->blockTxCount ?? '—';

        $walletTypeSummary = collect($this->walletTypes)
            ->map(fn($count, $type) => "- {$type}: {$count}")
            ->implode("\n");

        return <<<TEXT
Transaction Summary
-------------------

- TXID: {$this->txid}
- Confirmed: {$confirmedText}
- Block: {$block}
- Timestamp: {$timestamp}
- Miner: {$miner}
- Total TXs in Block: {$blockTxCountText}
- Fee: {$this->fee} sats
- Inputs: {$this->inputCount}
- Outputs: {$this->outputCount}
- Total Input: {$this->totalInput} sats
- Total Output: {$this->totalOutput} sats
- OP_RETURN present: {$opReturn}
- MultiSig Output: {$multisig}
- Among top fee payers: {$topFee}

Inferred Wallet Types:
{$walletTypeSummary}

Behavior Flags:
- CoinJoin-like: {$coinjoin}
- Consolidation-like: {$consolidation}
TEXT;
    }
}
