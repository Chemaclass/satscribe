<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Payment\Domain\AlbyClientInterface;
use Modules\Payment\Domain\ConfirmInvoicePaymentActionInterface;
use Modules\Payment\Domain\Data\InvoiceData;
use Modules\Shared\Domain\RateLimit\RateLimitKeys;
use Tests\TestCase;

/**
 * Clearing the paywall used to depend entirely on the Alby webhook, which maps a
 * payment back to a visitor through a cache entry that expires when the invoice
 * does. Pay a few minutes late and the mapping was gone: the payment was
 * recorded and the visitor stayed paywalled.
 *
 * The status poll is made by the payer, so it can lift their own paywall with no
 * mapping involved.
 */
final class ConfirmInvoicePaymentTest extends TestCase
{
    use RefreshDatabase;

    private const HASH = 'payment-hash-1';

    public function test_confirming_your_own_settled_invoice_clears_the_paywall(): void
    {
        $this->fakeAlby(paid: true);
        $trackingId = tracking_id();
        $this->exhaustTheLimit($trackingId);
        $this->cacheInvoiceFor($trackingId, self::HASH);

        $paid = $this->action()->execute(self::HASH, $trackingId);

        self::assertTrue($paid);
        self::assertSame(0, RateLimiter::attempts(RateLimitKeys::forTrackingId($trackingId)));
    }

    public function test_an_unpaid_invoice_leaves_the_paywall_in_place(): void
    {
        $this->fakeAlby(paid: false);
        $trackingId = tracking_id();
        $this->exhaustTheLimit($trackingId);
        $this->cacheInvoiceFor($trackingId, self::HASH);

        self::assertFalse($this->action()->execute(self::HASH, $trackingId));
        self::assertGreaterThan(0, RateLimiter::attempts(RateLimitKeys::forTrackingId($trackingId)));
    }

    /**
     * Otherwise any settled payment hash — someone else's, or one found in a
     * public explorer — would lift the caller's own paywall for free.
     */
    public function test_a_settled_invoice_that_is_not_yours_does_not_clear_your_paywall(): void
    {
        $this->fakeAlby(paid: true);
        $trackingId = tracking_id();
        $this->exhaustTheLimit($trackingId);
        $this->cacheInvoiceFor($trackingId, 'my-own-invoice');

        $paid = $this->action()->execute('someone-elses-invoice', $trackingId);

        self::assertTrue($paid);
        self::assertGreaterThan(0, RateLimiter::attempts(RateLimitKeys::forTrackingId($trackingId)));
    }

    public function test_the_same_payment_cannot_clear_the_paywall_twice(): void
    {
        $this->fakeAlby(paid: true);
        $trackingId = tracking_id();
        $this->cacheInvoiceFor($trackingId, self::HASH);

        $this->action()->execute(self::HASH, $trackingId);
        $this->exhaustTheLimit($trackingId);
        $this->action()->execute(self::HASH, $trackingId);

        self::assertGreaterThan(0, RateLimiter::attempts(RateLimitKeys::forTrackingId($trackingId)));
    }

    public function test_the_status_endpoint_reports_and_clears(): void
    {
        $this->fakeAlby(paid: true);
        $trackingId = tracking_id();
        $this->exhaustTheLimit($trackingId);
        $this->cacheInvoiceFor($trackingId, self::HASH);

        $response = $this->getJson('/api/invoice/' . self::HASH . '/status');

        $response->assertStatus(200);
        $response->assertJsonPath('paid', true);
        self::assertSame(0, RateLimiter::attempts(RateLimitKeys::forTrackingId($trackingId)));
    }

    private function action(): ConfirmInvoicePaymentActionInterface
    {
        return app(ConfirmInvoicePaymentActionInterface::class);
    }

    private function exhaustTheLimit(string $trackingId): void
    {
        $key = RateLimitKeys::forTrackingId($trackingId);
        $max = (int) config('services.rate_limit.guest.max_attempts');

        for ($i = 0; $i <= $max; ++$i) {
            RateLimiter::hit($key, 3600);
        }
    }

    private function cacheInvoiceFor(string $trackingId, string $paymentHash): void
    {
        Cache::put(
            RateLimitKeys::forInvoice(RateLimitKeys::shortHashFor($trackingId)),
            ['payment_hash' => $paymentHash, 'payment_request' => 'lnbc1'],
            now()->addMinutes(5),
        );
    }

    private function fakeAlby(bool $paid): void
    {
        $alby = new class($paid) implements AlbyClientInterface {
            public function __construct(private readonly bool $paid)
            {
            }

            public function isConnectionValid(): bool
            {
                return true;
            }

            public function createInvoice(InvoiceData $invoice): array
            {
                return ['id' => 'unused', 'r_hash' => 'unused', 'payment_hash' => 'unused'];
            }

            public function isInvoicePaid(string $hash): bool
            {
                return $this->paid;
            }

            public function getInvoice(string $hash): array
            {
                return [];
            }
        };

        $this->app->instance(AlbyClientInterface::class, $alby);
    }
}
