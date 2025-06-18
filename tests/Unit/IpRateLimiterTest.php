<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Middleware\IpRateLimiter;
use PHPUnit\Framework\TestCase;

final class IpRateLimiterTest extends TestCase
{

    public function test_create_rate_limit_key(): void
    {
        $this->assertSame(
            'ip_rate_limit_abc',
            IpRateLimiter::createRateLimitKey('abc')
        );
    }

    public function test_create_cache_key(): void
    {
        $this->assertSame(
            'invoice_tracking_mapping_123',
            IpRateLimiter::createCacheKey('123')
        );
    }

}
