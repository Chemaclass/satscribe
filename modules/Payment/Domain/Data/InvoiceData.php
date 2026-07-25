<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Data;

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
     * @return array{
     *     amount: int,
     *     memo: string,
     *     description?: string,
     *     description_hash?: string,
     *     expiry: int,
     * }
     */
    public function toArray(): array
    {
        return array_filter([
            'amount' => $this->amount,
            'memo' => $this->memo,
            'description' => $this->description,
            'description_hash' => $this->descriptionHash,
            'expiry' => $this->expiry,
        ], static fn ($value) => $value !== null);
    }
}
