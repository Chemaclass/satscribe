<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Data\Blockchain;

/**
 * Raw Blockstream payloads. Only the keys this codebase reads are listed and
 * every shape is unsealed, since the API returns more.
 *
 * @phpstan-type TRawVout array{
 *     scriptpubkey?: string,
 *     scriptpubkey_address?: string,
 *     scriptpubkey_type?: string,
 *     value?: int,
 *     ...
 * }
 * @phpstan-type TRawTx array{
 *     txid?: string,
 *     fee?: int,
 *     vin?: list<array<string, mixed>>,
 *     vout?: list<TRawVout>,
 *     ...
 * }
 * @phpstan-type TRawBlock array{
 *     id: string,
 *     height: int,
 *     version: int,
 *     timestamp: int,
 *     tx_count: int,
 *     size: int,
 *     weight: int,
 *     merkle_root: string,
 *     previousblockhash?: string|null,
 *     mediantime: int,
 *     nonce: int,
 *     bits: int,
 *     difficulty: float,
 *     ...
 * }
 */
final readonly class BlockData implements BlockchainDataInterface
{
    /**
     * @param  list<TRawTx>  $transactions  raw Blockstream transactions
     */
    public function __construct(
        public string $hash,
        public int $height = 0,
        public int $version = 0,
        public int $timestamp = 0,
        public int $txCount = 0,
        public int $size = 0,
        public int $weight = 0,
        public string $merkleRoot = '',
        public ?string $previousBlockHash = null,
        public int $medianTime = 0,
        public int $nonce = 0,
        public int $bits = 0,
        public float $difficulty = 0.0,
        public array $transactions = [],
        public ?string $coinbaseMessage = null,
    ) {
    }

    /**
     * @param  TRawBlock  $data
     * @param  list<TRawTx>  $transactions
     */
    public static function fromArray(array $data, array $transactions = []): self
    {
        $coinbaseMessage = null;

        if (!empty($transactions[0]['vin'][0]['scriptsig'])) {
            $coinbaseScript = $transactions[0]['vin'][0]['scriptsig'];
            $coinbaseMessage = self::decodeCoinbaseScript($coinbaseScript);
        }

        return new self(
            hash: $data['id'],
            height: $data['height'],
            version: $data['version'],
            timestamp: $data['timestamp'],
            txCount: $data['tx_count'],
            size: $data['size'],
            weight: $data['weight'],
            merkleRoot: $data['merkle_root'],
            previousBlockHash: $data['previousblockhash'] ?? null,
            medianTime: $data['mediantime'],
            nonce: $data['nonce'],
            bits: $data['bits'],
            difficulty: $data['difficulty'],
            transactions: $transactions,
            coinbaseMessage: $coinbaseMessage,
        );
    }

    public static function decodeCoinbaseScript(string $hex): ?string
    {
        $binary = hex2bin($hex);
        if ($binary === false) {
            return null;
        }

        // Extract readable ASCII characters
        $ascii = preg_replace('/[^[:print:]]/', '', $binary);
        return trim((string) $ascii);
    }

    /**
     * @return array{
     *     hash: string,
     *     height: int,
     *     version: int,
     *     timestamp: int,
     *     tx_count: int,
     *     size: int,
     *     weight: int,
     *     merkle_root: string,
     *     previousblockhash: string|null,
     *     mediantime: int,
     *     nonce: int,
     *     bits: int,
     *     difficulty: float,
     *     transactions: list<TRawTx>,
     *     coinbase_message: string|null,
     * }
     */
    public function toArray(): array
    {
        return [
            'hash' => $this->hash,
            'height' => $this->height,
            'version' => $this->version,
            'timestamp' => $this->timestamp,
            'tx_count' => $this->txCount,
            'size' => $this->size,
            'weight' => $this->weight,
            'merkle_root' => $this->merkleRoot,
            'previousblockhash' => $this->previousBlockHash,
            'mediantime' => $this->medianTime,
            'nonce' => $this->nonce,
            'bits' => $this->bits,
            'difficulty' => $this->difficulty,
            'transactions' => $this->transactions,
            'coinbase_message' => $this->coinbaseMessage,
        ];
    }

    public function toPrompt(): string
    {
        return BlockSummary::from($this)->toPrompt();
    }
}
