<?php

declare(strict_types=1);

namespace App\Data\Blockchain;

use App\Data\BlockchainData;

final readonly class BlockData implements BlockchainData
{
    public function __construct(
        public string $hash,
        public int $height,
        public int $version,
        public int $timestamp,
        public int $txCount,
        public int $size,
        public int $weight,
        public string $merkleRoot,
        public string $previousBlockHash,
        public int $medianTime,
        public int $nonce,
        public int $bits,
        public float $difficulty,
        public array $transactions,
    ) {
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
        ];
    }

    public function toPrompt(): string
    {
        return BlockSummary::from($this)->toPrompt();
    }
}
