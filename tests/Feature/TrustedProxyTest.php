<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * The app sits behind kamal-proxy, so REMOTE_ADDR is the proxy and every
 * visitor looks like the same client. tracking_id() keys the guest rate limit
 * on client_ip(), so without trusted proxies five guests exhaust the quota for
 * everyone.
 */
final class TrustedProxyTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_forwarded_client_ip_is_used_behind_the_proxy(): void
    {
        Route::get('/__test_ip', static fn (): string => (string) request()->ip());

        $response = $this->get('/__test_ip', ['X-Forwarded-For' => '203.0.113.9']);

        $response->assertOk();
        self::assertSame('203.0.113.9', $response->getContent());
    }

    public function test_two_forwarded_clients_get_separate_rate_limit_buckets(): void
    {
        Route::get('/__test_tracking', static fn (): string => tracking_id());

        $first = $this->get('/__test_tracking', ['X-Forwarded-For' => '203.0.113.9'])->getContent();
        $second = $this->get('/__test_tracking', ['X-Forwarded-For' => '198.51.100.4'])->getContent();

        self::assertNotSame($first, $second);
    }
}
