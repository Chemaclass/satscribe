<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Data;

use InvalidArgumentException;

final readonly class InvoiceData
{
    public function __construct(
        public int $amount,
        public string $memo = 'Tip to unlock more requests',
        public ?string $description = null,
        public ?string $descriptionHash = null,
        public int $expiry = 3600,
    ) {
    }

    /**
     * @param  array{
     *     amount: int,
     *     memo: string,
     *     description: string,
     *     description_hash: string,
     *     expiry: int,
     * }  $data
     */
    public static function create(array $data): self
    {
        if (!isset($data['amount'])) {
            throw new InvalidArgumentException('Amount is required');
        }

        if ($data['amount'] <= 0) {
            throw new InvalidArgumentException('Amount must be positive');
        }

        return new self(
            amount: $data['amount'],
            memo: $data['memo'] ?? 'Tip to unlock more requests',
            description: $data['description'] ?? null,
            descriptionHash: $data['description_hash'] ?? null,
            expiry: $data['expiry'] ?? 3600,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'amount' => $this->amount,
            'memo' => $this->memo,
            'description' => $this->description,
            'description_hash' => $this->descriptionHash,
            'expiry' => $this->expiry,
        ], static fn($value) => $value !== null);
    }
}
