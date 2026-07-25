<?php

declare(strict_types=1);

namespace Modules\Payment\Domain;

use Modules\Shared\Domain\Data\Payment\InvoiceData;

interface AlbyClientInterface
{
    public function isConnectionValid(): bool;

    /**
     * Create a new Lightning invoice.
     *
     * `id` and `r_hash` are added client-side as aliases of `payment_hash`.
     * The remaining keys are Alby's, of which only those consumed by
     * CachedInvoiceValidator are listed; the shape is intentionally unsealed.
     *
     * @return array{
     *     id: string,
     *     r_hash: string,
     *     payment_hash: string,
     *     payment_request: string,
     *     created_at: string,
     *     expiry: int,
     *     ...
     * }
     */
    public function createInvoice(InvoiceData $invoice): array;

    /**
     * @return array<string, mixed> raw Alby invoice payload
     */
    public function getInvoice(string $hash): array;

    public function isInvoicePaid(string $hash): bool;
}
