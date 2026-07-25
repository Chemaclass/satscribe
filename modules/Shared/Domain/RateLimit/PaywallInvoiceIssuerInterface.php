<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\RateLimit;

/**
 * The rate-limiting middleware lives in Shared but the invoice it offers on a
 * 429 is a Payment concern. Shared declares the port and Payment implements it,
 * so the dependency runs Payment -> Shared only; the middleware knows nothing
 * about Alby.
 */
interface PaywallInvoiceIssuerInterface
{
    /**
     * @param  string  $reference  short opaque id tying the invoice to the throttled caller
     *
     * @return array<string, mixed> the payload the client renders as a QR
     */
    public function issue(string $reference, int $amountSats, int $expirySeconds): array;

    /**
     * @param  array<string, mixed>|null  $cached  a previously issued payload
     */
    public function isReusable(?array $cached): bool;
}
