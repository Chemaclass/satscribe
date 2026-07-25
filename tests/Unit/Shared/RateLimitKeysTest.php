<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use Modules\Shared\Domain\RateLimit\RateLimitKeys;
use PHPUnit\Framework\TestCase;

final class RateLimitKeysTest extends TestCase
{
    public function test_for_tracking_id(): void
    {
        $this->assertSame(
            'ip_rate_limit_abc',
            RateLimitKeys::forTrackingId('abc'),
        );
    }

    public function test_for_invoice_tracking_mapping(): void
    {
        $this->assertSame(
            'invoice_tracking_mapping_123',
            RateLimitKeys::forInvoiceTrackingMapping('123'),
        );
    }
}
