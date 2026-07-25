<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\RateLimit;

/**
 * Cache key vocabulary shared by the rate-limiting middleware that mints
 * invoices and the payment webhook that settles them.
 */
final class RateLimitKeys
{
    public static function forTrackingId(string $trackingId): string
    {
        return 'ip_rate_limit_' . $trackingId;
    }

    public static function forInvoiceTrackingMapping(string $hash): string
    {
        return 'invoice_tracking_mapping_' . $hash;
    }

    public static function forInvoice(string $hash): string
    {
        return 'ln_invoice:' . $hash;
    }

    /**
     * Short, non-reversible handle for a tracking id. It travels in the invoice
     * memo, which is the only part of the payment that comes back from Alby, so
     * it is what ties a settled invoice to the visitor who asked for it.
     */
    public static function shortHashFor(string $trackingId): string
    {
        return substr(md5(self::forTrackingId($trackingId)), 0, 8);
    }
}
