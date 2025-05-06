<?php

declare(strict_types=1);

namespace App\Data\Blockchain;

use App\Services\MinerIdentifier;
use Illuminate\Support\Collection;

final class BlockSummary
{
    public function __construct(
        public int $height,
        public int $txCount,
        public int $size,
        public int $weight,
        public int $timestamp,
        public ?string $miner,
        public int $coinbaseValue,
        public ?string $coinbaseMessage,
        public bool $hasOpReturnInCoinbase,
        public array $topTransactionsByFee,
        public array $walletTypesBreakdown,
    ) {
    }

    public static function from(BlockData $data): self
    {
        $coinbaseTx = $data->transactions[0] ?? [];
        $scriptsig = $coinbaseTx['vin'][0]['scriptsig'] ?? '';
        $miner = MinerIdentifier::extractFromCoinbaseHex($scriptsig);
        $coinbaseOutputs = $coinbaseTx['vout'] ?? [];

        $coinbaseValue = array_sum(array_column($coinbaseOutputs, 'value'));

        $hasOpReturn = collect($coinbaseOutputs)
            ->contains(fn($out) => $out['scriptpubkey_type'] === 'op_return');

        $topFees = collect($data->transactions)
            ->filter(fn($tx) => isset($tx['fee']))
            ->sortByDesc('fee')
            ->take(3)
            ->map(fn($tx) => [
                'txid' => $tx['txid'] ?? null,
                'fee' => $tx['fee'],
            ])
            ->values()
            ->all();

        $walletTypes = collect($data->transactions)
            ->flatMap(fn($tx) => $tx['vout'] ?? [])
            ->groupBy('scriptpubkey_type')
            ->map(fn(Collection $items) => $items->count())
            ->sortKeys()
            ->toArray();

        return new self(
            height: $data->height,
            txCount: $data->txCount,
            size: $data->size,
            weight: $data->weight,
            timestamp: $data->timestamp,
            miner: $miner,
            coinbaseValue: $coinbaseValue,
            coinbaseMessage: $data->coinbaseMessage,
            hasOpReturnInCoinbase: $hasOpReturn,
            topTransactionsByFee: $topFees,
            walletTypesBreakdown: $walletTypes,
        );
    }

    public function toPrompt(): string
    {
        $opReturnText = $this->hasOpReturnInCoinbase ? 'Yes' : 'No';
        $minerText = $this->miner ?? 'Unknown miner';

        $topTxs = collect($this->topTransactionsByFee)->map(
            fn($tx, $i) => sprintf("%d. %s (Fee: %s sats)", $i + 1, $tx['txid'] ?? 'N/A', number_format($tx['fee']))
        )->implode("\n");

        $walletTypeDescriptions = [
            'p2pk' => 'P2PK: Full public keys directly',
            'p2pkh' => 'P2PKH: Legacy (starts with 1)',
            'p2sh' => 'P2SH: Script (starts with 3)',
            'p2wpkh' => 'P2WPKH: Native SegWit (starts with bc1)',
            'p2wsh' => 'P2WSH: SegWit complex scripts',
            'p2tr' => 'P2TR: Taproot (starts with bc1p)',
            'p2ms' => 'P2MS: Multisig scripts',
            'op_return' => 'OP_RETURN: Data-carrying txs',
        ];

        $walletSummary = collect($this->walletTypesBreakdown)->map(
            fn($count, $type) => sprintf('- %s: %d', $walletTypeDescriptions[$type] ?? strtoupper($type), $count)
        )->implode("\n");

        return <<<TEXT
Block Summary
-------------

- Height: {$this->height}
- Timestamp: {$this->timestamp}
- Miner: {$minerText}
- Coinbase Message: {$this->coinbaseMessage}
- Coinbase Value: {$this->coinbaseValue} sats
- OP_RETURN in coinbase: {$opReturnText}
- Total Transactions: {$this->txCount}
- Size: {$this->size} bytes
- Weight: {$this->weight} units

Top transactions by fee:
{$topTxs}

Wallet Types Breakdown:
{$walletSummary}
TEXT;
    }
}
