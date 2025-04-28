<?php

namespace App\Services\Alby;

use App\Data\InvoiceData;

interface AlbyClientInterface
{
    public function isConnectionValid(): bool;

    public function getInfo(): array;

    public function createInvoice(InvoiceData $invoice): array;

    public function getInvoice(string $hash): array;

    public function isInvoicePaid(string $hash): bool;
}
