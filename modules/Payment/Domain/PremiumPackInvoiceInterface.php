<?php

declare(strict_types=1);

namespace Modules\Payment\Domain;

interface PremiumPackInvoiceInterface
{
    /**
     * Mints an invoice for a pack of premium messages and remembers which
     * identity it belongs to, so the settlement can find its way back.
     *
     * @return array<string, mixed> the payload the client renders as a QR
     */
    public function issueFor(string $npub): array;

    /**
     * The identity a settled pack reference belongs to, or null when the
     * mapping has gone.
     */
    public function identityFor(string $reference): ?string;
}
