<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Data\Blockchain;

final readonly class BlockData implements BlockchainDataInterface
{
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

    public function getType(): string
    {
        return 'block';
    }

    public function getInput(): string
    {
        return (string) $this->height;
    }

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
