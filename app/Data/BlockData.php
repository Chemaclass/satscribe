<?php
declare(strict_types=1);

namespace App\Data;

final readonly class BlockData implements BlockchainData
{
    public function __construct(
        public string $hash,
        public int $height,
        public int $timestamp,
        public int $totalTransactions,
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
            'timestamp' => $this->timestamp,
            'totalTransactions' => $this->totalTransactions,
            'transactions' => $this->transactions,
        ];
    }
}
