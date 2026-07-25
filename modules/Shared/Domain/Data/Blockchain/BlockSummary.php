<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Data\Blockchain;

use Illuminate\Support\Collection;

use function is_string;
use function sprintf;

/**
 * @phpstan-import-type TRawVout from BlockData
 */
final class BlockSummary
{
    /**
     * @param  list<array{txid: string|null, fee: int}>  $topTransactionsByFee
     * @param  array<string, int>  $walletTypesBreakdown  script type => output count
     */
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

        // scriptsig is a raw Blockstream field: absent on a malformed coinbase
        // and not guaranteed to be a string, which extractFromCoinbaseHex
        // would reject outright rather than treat as an unknown miner.
        $scriptsig = $coinbaseTx['vin'][0]['scriptsig'] ?? null;
        $miner = MinerIdentifier::extractFromCoinbaseHex(is_string($scriptsig) ? $scriptsig : '');
        /** @var list<TRawVout> $coinbaseOutputs */
        $coinbaseOutputs = $coinbaseTx['vout'] ?? [];

        $coinbaseValue = array_sum(array_column($coinbaseOutputs, 'value'));

        $hasOpReturn = collect($coinbaseOutputs)
            ->contains(static fn ($out) => ($out['scriptpubkey_type'] ?? null) === 'op_return');

        $topFees = collect($data->transactions)
            ->filter(static fn ($tx) => isset($tx['fee']))
            ->sortByDesc('fee')
            ->take(3)
            ->map(static fn ($tx) => [
                'txid' => $tx['txid'] ?? null,
                'fee' => $tx['fee'],
            ])
            ->values()
            ->all();
        $topFees = array_values($topFees);

        /** @var array<string, int> $walletTypes */
        $walletTypes = collect($data->transactions)
            ->flatMap(static fn ($tx) => $tx['vout'] ?? [])
            ->groupBy('scriptpubkey_type')
            ->map(static fn (Collection $items) => $items->count())
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
            static fn ($tx, $i) => sprintf('%d. %s (Fee: %s sats)', $i + 1, $tx['txid'] ?? 'N/A', number_format($tx['fee'])),
        )->implode("\n");

        // Keyed on the values Blockstream actually emits. Esplora versions the
        // witness types (`v0_p2wpkh`, `v0_p2wsh`, `v1_p2tr`) and calls bare
        // multisig `multisig`, so the unversioned spellings never matched and
        // the commonest output types in a modern block fell through to their
        // raw uppercase code. The unversioned keys stay as aliases.
        $walletTypeDescriptions = [
            'p2pk' => 'P2PK: Full public keys directly',
            'p2pkh' => 'P2PKH: Legacy (starts with 1)',
            'p2sh' => 'P2SH: Script (starts with 3)',
            'v0_p2wpkh' => 'P2WPKH: Native SegWit (starts with bc1)',
            'p2wpkh' => 'P2WPKH: Native SegWit (starts with bc1)',
            'v0_p2wsh' => 'P2WSH: SegWit complex scripts',
            'p2wsh' => 'P2WSH: SegWit complex scripts',
            'v1_p2tr' => 'P2TR: Taproot (starts with bc1p)',
            'p2tr' => 'P2TR: Taproot (starts with bc1p)',
            'multisig' => 'P2MS: Multisig scripts',
            'p2ms' => 'P2MS: Multisig scripts',
            'op_return' => 'OP_RETURN: Data-carrying txs',
        ];

        $walletSummary = collect($this->walletTypesBreakdown)->map(
            static fn ($count, $type) => sprintf('- %s: %d', $walletTypeDescriptions[$type] ?? strtoupper($type), $count),
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
