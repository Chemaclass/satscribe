<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Data\Blockchain;

/**
 * `vin`/`vout` hold raw Blockstream input/output objects. Their inner keys vary
 * by script type (and `vin` entries may carry a nested `prevout`), so they stay
 * unsealed rather than being given a shape the API does not guarantee.
 */
final readonly class TransactionData implements BlockchainDataInterface
{
    /**
     * @param  list<array<string, mixed>>  $vin
     * @param  list<array<string, mixed>>  $vout
     */
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

    /**
     * @return array{
     *     txid: string,
     *     version: int,
     *     locktime: int,
     *     vin: list<array<string, mixed>>,
     *     vout: list<array<string, mixed>>,
     *     size: int,
     *     weight: int,
     *     fee: int,
     *     status: array{
     *         confirmed: bool,
     *         block_height: int|null,
     *         block_hash: string|null,
     *         block_time: int|null,
     *     },
     * }
     */
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
