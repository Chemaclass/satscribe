<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Data\Blockchain;

use function count;
use function is_array;
use function is_int;
use function is_string;

final class TransactionSummary
{
    /**
     * @param  array<string, int>  $walletTypes  script type => occurrence count
     */
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

        $totalInput = $inputs->sum(static fn (array $vin): int => self::intField(self::prevout($vin), 'value'));
        $totalOutput = $outputs->sum(static fn (array $out): int => self::intField($out, 'value'));

        $hasOpReturn = $outputs->contains(
            static fn (array $out): bool => ($out['scriptpubkey_type'] ?? null) === 'op_return',
        );
        $hasMultiSig = $outputs->contains(
            static fn (array $out): bool => ($out['scriptpubkey_type'] ?? null) === 'multisig',
        );

        $isTopFeePayer = $block instanceof BlockSummary && collect($block->topTransactionsByFee)
                ->pluck('txid')->contains($tx->txid);

        /** @var array<string, int> $walletTypes */
        $walletTypes = collect([...$inputs, ...$outputs])
            ->map(static fn (array $io): ?string => self::stringField(self::prevout($io), 'scriptpubkey_type')
                ?? self::stringField($io, 'scriptpubkey_type'))
            ->filter()
            ->countBy()
            ->toArray();

        $uniqueInputAddresses = $inputs
            ->map(static fn (array $vin): ?string => self::stringField(self::prevout($vin), 'scriptpubkey_address'))
            ->filter()
            ->unique();

        $outputValues = $outputs
            ->map(static fn (array $out): int => self::intField($out, 'value'))
            ->filter();

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
            ->map(static fn ($count, $type) => "- {$type}: {$count}")
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

    /**
     * Blockstream input/output objects are unsealed and their inner keys vary
     * by script type, so a field can be absent or the wrong type. Reading them
     * blind summed a non-numeric value straight into the totals — a TypeError
     * at best, a plausible-looking wrong number at worst, and these figures go
     * verbatim into the prompt the user reads as fact.
     *
     * @param  array<string, mixed>  $io
     *
     * @return array<string, mixed>
     */
    private static function prevout(array $io): array
    {
        $prevout = $io['prevout'] ?? null;

        return is_array($prevout) ? $prevout : [];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function intField(array $data, string $field): int
    {
        $value = $data[$field] ?? null;

        return is_int($value) ? $value : 0;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function stringField(array $data, string $field): ?string
    {
        $value = $data[$field] ?? null;

        return is_string($value) ? $value : null;
    }
}
