<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Data\Blockchain;

final readonly class TransactionData implements BlockchainDataInterface
{
    public function __construct(
        public string $txid,
        public int $version = 0,
        public int $locktime = 0,
        public array $vin = [],
        public array $vout = [],
        public int $size = 0,
        public int $weight = 0,
        public int $fee = 0,
        public bool $confirmed = false,
        public ?int $blockHeight = null,
        public ?string $blockHash = null,
        public ?int $blockTime = null,
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
