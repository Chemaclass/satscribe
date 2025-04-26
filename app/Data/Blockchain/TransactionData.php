<?php

declare(strict_types=1);

namespace App\Data\Blockchain;

use App\Data\BlockchainDataInterface;

final readonly class TransactionData implements BlockchainDataInterface
{
    public function __construct(
        public string $txid,
        public int $version,
        public int $locktime,
        public array $vin,
        public array $vout,
        public int $size,
        public int $weight,
        public int $fee,
        public bool $confirmed,
        public ?int $blockHeight,
        public ?string $blockHash,
        public ?int $blockTime,
    ) {
    }

    public function getType(): string
    {
        return 'transaction';
    }

    public function getInput(): string
    {
        return $this->txid;
    }

    public function toArray(): array
    {
        return [
            'txid' => $this->txid,
            'version' => $this->version,
            'locktime' => $this->locktime,
            'vin' => $this->vin,
            'vout' => $this->vout,
            'size' => $this->size,
            'weight' => $this->weight,
            'fee' => $this->fee,
            'status' => [
                'confirmed' => $this->confirmed,
                'block_height' => $this->blockHeight,
                'block_hash' => $this->blockHash,
                'block_time' => $this->blockTime,
            ],
        ];
    }

    public function toPrompt(): string
    {
        return TransactionSummary::from($this)->toPrompt();
    }
}
