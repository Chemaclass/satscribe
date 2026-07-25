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
     * `id` and `r_hash` are added client-side as aliases of `payment_hash`,
     * which is the only key the client validates — hence the only one that can
     * be guaranteed. Alby also returns `payment_request`, `created_at` and
     * `expiry`, which CachedInvoiceValidator checks for at runtime; they are
     * deliberately NOT declared here, since nothing verifies them on this path.
     *
     * @return array{id: string, r_hash: string, payment_hash: string, ...}
     */
    public function createInvoice(InvoiceData $invoice): array;

    /**
     * @return array<string, mixed> raw Alby invoice payload
     */
    public function getInvoice(string $hash): array;

    public function isInvoicePaid(string $hash): bool;
}
