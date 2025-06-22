<?php

namespace Modules\Payment\Domain;

use Modules\Shared\Domain\Data\Payment\InvoiceData;

interface AlbyClientInterface
{
    public function isConnectionValid(): bool;

    public function getInfo(): array;

    /**
     * Create a new Lightning invoice.
     *
     * @return array{
     *     id: string,
     *     r_hash: string,
     *     payment_hash: string,
     *     expiry: int
     * }
     */
    public function createInvoice(InvoiceData $invoice): array;

    public function getInvoice(string $hash): array;

    public function isInvoicePaid(string $hash): bool;
}
