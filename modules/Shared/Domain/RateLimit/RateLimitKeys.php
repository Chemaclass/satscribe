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
}
