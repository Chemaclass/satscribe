<?php
declare(strict_types=1);

namespace App\Data;

final class TransactionData implements BlockchainData
{
    public function __construct(
        public readonly string $txid,
        public readonly array $status,
        public readonly int $version,
        public readonly int $locktime,
        public readonly array $vin,
        public readonly array $vout,
        public readonly int $size,
        public readonly int $weight,
        public readonly int $fee
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
