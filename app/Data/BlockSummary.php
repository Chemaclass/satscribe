<?php

declare(strict_types=1);

namespace App\Data;

use App\Services\MinerIdentifier;

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
        public bool $hasOpReturnInCoinbase,
        public array $topTransactionsByFee,
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

        return new self(
            height: $data->height,
            txCount: $data->txCount,
            size: $data->size,
            weight: $data->weight,
            timestamp: $data->timestamp,
            miner: $miner,
            coinbaseValue: $coinbaseValue,
            hasOpReturnInCoinbase: $hasOpReturn,
            topTransactionsByFee: $topFees,
        );
    }

    private static function extractMinerFromCoinbaseHex(string $scriptsig): ?string
    {
        $ascii = preg_replace('/[^[:print:]]/', '', hex2bin($scriptsig));

        $knownPools = [
            'MARA' => 'MARA Pool',
            'Foundry' => 'Foundry USA',
            'AntPool' => 'AntPool',
            'Binance' => 'Binance Pool',
            'F2Pool' => 'F2Pool',
            'ViaBTC' => 'ViaBTC',
            'Luxor' => 'Luxor',
        ];

        foreach ($knownPools as $key => $label) {
            if (stripos($ascii, $key) !== false) {
                return $label;
            }
        }

        return null;
    }

    public function toPrompt(): string
    {
        dump($this);
        $opReturnText = $this->hasOpReturnInCoinbase ? 'Yes' : 'No';
        $minerText = $this->miner ?? 'Unknown miner';

        $topTxs = collect($this->topTransactionsByFee)->map(fn($tx, $i) => sprintf("%d. %s (Fee: %s sats)", $i + 1,
            $tx['txid'] ?? 'N/A', number_format($tx['fee']))
        )->implode("\n");

        return <<<TEXT
Block Summary
-------------

- Height: {$this->height}
- Timestamp: {$this->timestamp}
- Miner: {$minerText}
- Coinbase Value: {$this->coinbaseValue} sats
- OP_RETURN in coinbase: {$opReturnText}
- Total Transactions: {$this->txCount}
- Size: {$this->size} bytes
- Weight: {$this->weight} units

Top transactions by fee:
{$topTxs}
TEXT;
    }
}
