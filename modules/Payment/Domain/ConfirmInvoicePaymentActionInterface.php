<?php

declare(strict_types=1);

namespace Modules\Payment\Domain;

interface ConfirmInvoicePaymentActionInterface
{
    /**
     * Reports whether the invoice is settled and, if it is the visitor's own
     * invoice, lifts their paywall immediately.
     *
     * The Alby webhook does the same thing, but it has to map a payment back to
     * a visitor through a short-lived cache entry. The poller is the payer, so
     * here the visitor is already known and no mapping can expire underneath it.
     */
    public function execute(string $identifier, string $trackingId): bool;
}
