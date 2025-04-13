<?php
declare(strict_types=1);

namespace App\Data;

final readonly class TransactionData implements BlockchainData
{
    public function __construct(
        public string $txid,
        public array $status,
        public int $version,
        public int $locktime,
        public array $vin,
        public array $vout,
        public int $size,
        public int $weight,
        public int $fee
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
            'status' => $this->status,
            'version' => $this->version,
            'locktime' => $this->locktime,
            'vin' => $this->vin,
            'vout' => $this->vout,
            'size' => $this->size,
            'weight' => $this->weight,
            'fee' => $this->fee,
        ];
    }
}
