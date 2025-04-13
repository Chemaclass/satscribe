<?php
declare(strict_types=1);

namespace App\Data;

final class BlockData implements BlockchainData
{
    public function __construct(
        public readonly string $hash,
        public readonly int $height,
        public readonly int $timestamp,
        public readonly array $transactions
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
            'transactions' => $this->transactions,
        ];
    }
}
