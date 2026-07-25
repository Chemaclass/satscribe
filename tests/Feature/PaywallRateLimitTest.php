<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Payment\Domain\AlbyClientInterface;
use Modules\Payment\Domain\Data\InvoiceData;
use Modules\Shared\Domain\RateLimit\RateLimitKeys;
use Tests\TestCase;

/**
 * The paywall is the only money path in the app and had no coverage. These
 * assert it through the route rather than against the middleware's collaborators,
 * so they stay valid across changes to how the invoice is obtained.
 */
final class PaywallRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_exhausted_limit_returns_the_invoice_the_client_renders(): void
    {
        $alby = $this->fakeAlby();
        $this->exhaustTheLimit();

        $response = $this->postJson('/', ['search' => '210000']);

        $response->assertStatus(429);
        $response->assertJsonPath('status', 'rate_limited');
        $response->assertJsonPath('invoice.payment_request', 'lnbc1invoice');
        $response->assertJsonPath('invoice.payment_hash', 'hash-1');
        self::assertSame(1, $alby->created);
    }

    /**
     * The invoice is cached because minting one costs an Alby round trip; a
     * second paywalled request must reuse it rather than issue another.
     */
    public function test_a_second_paywalled_request_reuses_the_cached_invoice(): void
    {
        config(['services.rate_limit.invoice_expiry' => 300]);
        $alby = $this->fakeAlby();
        $this->exhaustTheLimit();

        $this->postJson('/', ['search' => '210000'])->assertStatus(429);
        $second = $this->postJson('/', ['search' => '210000']);

        $second->assertStatus(429);
        $second->assertJsonPath('invoice.payment_request', 'lnbc1invoice');
        self::assertSame(1, $alby->created);
    }

    /**
     * An expiry inside the caching margin leaves no window worth caching, so
     * each request mints a fresh invoice instead of serving one about to die.
     */
    public function test_an_expiry_within_the_caching_margin_mints_a_fresh_invoice(): void
    {
        config(['services.rate_limit.invoice_expiry' => 10]);
        $alby = $this->fakeAlby();
        $this->exhaustTheLimit();

        $this->postJson('/', ['search' => '210000'])->assertStatus(429);
        $this->postJson('/', ['search' => '210000'])->assertStatus(429);

        self::assertSame(2, $alby->created);
    }

    public function test_the_invoice_carries_the_configured_amount_and_expiry(): void
    {
        $alby = $this->fakeAlby();
        $this->exhaustTheLimit();

        $this->postJson('/', ['search' => '210000'])->assertStatus(429);

        self::assertInstanceOf(InvoiceData::class, $alby->lastInvoice);
        self::assertSame(
            config_int('services.rate_limit.guest.invoice_amount'),
            $alby->lastInvoice->amount,
        );
        self::assertSame(
            config_int('services.rate_limit.invoice_expiry'),
            $alby->lastInvoice->expiry,
        );
    }

    private function exhaustTheLimit(): void
    {
        $key = RateLimitKeys::forTrackingId(tracking_id());
        $max = config_int('services.rate_limit.guest.max_attempts');

        for ($i = 0; $i <= $max; ++$i) {
            RateLimiter::hit($key, 3600);
        }
    }

    private function fakeAlby(): RecordingAlbyClient
    {
        $alby = new RecordingAlbyClient();

        $this->app->instance(AlbyClientInterface::class, $alby);

        return $alby;
    }
}

/**
 * Counts invoices minted so the tests can tell a cache reuse from a fresh
 * Alby round trip, which is the only externally visible difference.
 */
final class RecordingAlbyClient implements AlbyClientInterface
{
    public int $created = 0;

    public ?InvoiceData $lastInvoice = null;

    public function isConnectionValid(): bool
    {
        return true;
    }

    public function createInvoice(InvoiceData $invoice): array
    {
        ++$this->created;
        $this->lastInvoice = $invoice;

        return [
            'payment_hash' => 'hash-1',
            'payment_request' => 'lnbc1invoice',
            'created_at' => now()->toIso8601String(),
            'expiry' => $invoice->expiry,
            'id' => 'hash-1',
            'r_hash' => 'hash-1',
        ];
    }

    public function isInvoicePaid(string $hash): bool
    {
        return false;
    }

    public function getInvoice(string $hash): array
    {
        return [];
    }
}
