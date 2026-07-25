<?php

declare(strict_types=1);

namespace Modules\Payment\Application;

use Modules\Payment\Domain\AlbyClientInterface;
use Modules\Payment\Domain\CachedInvoiceValidatorInterface;
use Modules\Payment\Domain\Data\InvoiceData;
use Modules\Payment\Domain\Data\InvoiceMemo;
use Modules\Shared\Domain\RateLimit\PaywallInvoiceIssuerInterface;

/**
 * Payment's side of the rate-limit paywall: everything Alby-shaped that the
 * middleware used to reach across module boundaries to do itself.
 */
final readonly class PaywallInvoiceIssuer implements PaywallInvoiceIssuerInterface
{
    public function __construct(
        private AlbyClientInterface $albyClient,
        private CachedInvoiceValidatorInterface $validator,
    ) {
    }

    public function issue(string $reference, int $amountSats, int $expirySeconds): array
    {
        return $this->albyClient->createInvoice(new InvoiceData(
            amount: $amountSats,
            memo: InvoiceMemo::forPaywall($reference),
            expiry: $expirySeconds,
        ));
    }

    public function isReusable(?array $cached): bool
    {
        return $this->validator->isValidCachedInvoice($cached);
    }
}
