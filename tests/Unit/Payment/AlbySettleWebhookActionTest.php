<?php

declare(strict_types=1);

namespace Svix;

class Webhook
{
    /** @var list<array{string, array<string, string>}> */
    public array $calls = [];

    public function __construct(private readonly string $secret)
    {
    }

    /**
     * @param  array<string, string>  $headers
     */
    public function verify(string $payload, array $headers): void
    {
        if ($this->secret === 'throw') {
            throw new \RuntimeException('invalid');
        }

        $this->calls[] = [$payload, $headers];
    }
}

namespace Tests\Unit\Payment;

use App\Models\Payment;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Cache\Repository;
use Modules\Payment\Application\AlbySettleWebhookAction;
use Modules\Payment\Domain\Exception\InvalidAlbyWebhookPayloadException;
use Modules\Payment\Domain\Exception\InvalidAlbyWebhookSignatureException;
use Modules\Payment\Domain\Repository\PaymentRepositoryInterface;
use Modules\Shared\Domain\RateLimit\RateLimitKeys;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class AlbySettleWebhookActionTest extends TestCase
{
    public function test_fails_when_secret_missing(): void
    {
        $cache = $this->createStub(Repository::class);
        $rate = new RecordingRateLimiter($this->createStub(Repository::class));
        $repo = $this->createStub(PaymentRepositoryInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $action = new AlbySettleWebhookAction('', $cache, $rate, $repo, $logger);

        $this->expectException(InvalidAlbyWebhookSignatureException::class);

        $action->execute('{}', 'id', 't', 's');
    }

    public function test_stores_invoice_and_clears_rate_limits(): void
    {
        $cache = new CacheRepository(new ArrayStore());
        $cache->forever(RateLimitKeys::forInvoiceTrackingMapping('deadbeef'), ['tracking_id' => 'track']);

        $rate = new RecordingRateLimiter($this->createStub(Repository::class));

        $repo = new class() implements PaymentRepositoryInterface {
            /** @var array<string, mixed> */
            public array $data = [];
            public function create(array $data): Payment
            {
                $this->data = $data;
                return new Payment();
            }
        };

        $logger = $this->createStub(LoggerInterface::class);

        $action = new AlbySettleWebhookAction('secret', $cache, $rate, $repo, $logger);

        $payload = json_encode([
            'payment_hash' => 'hash',
            'type' => 'incoming',
            'state' => 'SETTLED',
            'memo' => 'memo #deadbeef',
            'amount' => 1,
        ], JSON_THROW_ON_ERROR);

        $action->execute($payload, 'id', 't', 's');

        $this->assertSame('hash', $repo->data['payment_hash']);
        $this->assertContains(RateLimitKeys::forTrackingId('track'), $rate->cleared);
        $this->assertNull($cache->get(RateLimitKeys::forInvoice('deadbeef')));
    }

    /**
     * Alby sends more than one `incoming` event per invoice. The short-hash to
     * tracking-id mapping used to be read with pull(), so the first event
     * consumed it and the SETTLED one that followed had nothing to look up —
     * the visitor paid and stayed rate limited.
     */
    public function test_an_earlier_event_does_not_consume_the_tracking_mapping(): void
    {
        $cache = new CacheRepository(new ArrayStore());
        $cache->forever(RateLimitKeys::forInvoiceTrackingMapping('deadbeef'), ['tracking_id' => 'track']);

        $rate = new RecordingRateLimiter($this->createStub(Repository::class));
        $repo = new RecordingPaymentRepository();
        $logger = $this->createStub(LoggerInterface::class);

        $action = new AlbySettleWebhookAction('secret', $cache, $rate, $repo, $logger);

        $action->execute($this->payload('CREATED'), 'id', 't', 's');
        $this->assertNotContains(RateLimitKeys::forTrackingId('track'), $rate->cleared);

        $action->execute($this->payload('SETTLED'), 'id', 't', 's');
        $this->assertContains(RateLimitKeys::forTrackingId('track'), $rate->cleared);
    }

    /**
     * The parsing used to sit inside the same try as verify(), so a correctly
     * signed webhook with an unusable body was reported as a signature failure
     * — pointing an operator at a secret mismatch that does not exist.
     */
    public function test_a_signed_but_unusable_body_is_a_payload_error(): void
    {
        $action = new AlbySettleWebhookAction(
            'secret',
            new CacheRepository(new ArrayStore()),
            new RecordingRateLimiter($this->createStub(Repository::class)),
            new RecordingPaymentRepository(),
            $this->createStub(LoggerInterface::class),
        );

        $this->expectException(InvalidAlbyWebhookPayloadException::class);

        $action->execute('"just a string"', 'id', 't', 's');
    }

    private function payload(string $state): string
    {
        return json_encode([
            'payment_hash' => 'hash',
            'type' => 'incoming',
            'state' => $state,
            'memo' => 'memo #deadbeef',
            'amount' => 1,
        ], JSON_THROW_ON_ERROR);
    }
}

final class RecordingPaymentRepository implements PaymentRepositoryInterface
{
    /** @var array<string, mixed> */
    public array $data = [];

    public function create(array $data): Payment
    {
        $this->data = $data;

        return new Payment();
    }
}

/**
 * Records cleared keys without stubbing Laravel's class out of existence.
 * An earlier version of this test declared its own Illuminate\Cache\RateLimiter,
 * which shadowed the real one for the whole PHPUnit process and left it with a
 * single method, breaking any other test that used rate limiting.
 */
final class RecordingRateLimiter extends RateLimiter
{
    /** @var list<string> */
    public array $cleared = [];

    public function clear($key): void
    {
        $this->cleared[] = (string) $key;
    }
}
