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
use Modules\Payment\Domain\Data\InvoiceMemo;
use Modules\Payment\Domain\PremiumCreditsInterface;
use Modules\Payment\Domain\PremiumPackInvoiceInterface;
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

        $action = $this->action('', $cache, $rate, $repo);

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

        $action = $this->action('secret', $cache, $rate, $repo);

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

        $action = $this->action('secret', $cache, $rate, $repo);

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
        $action = $this->action('secret', new CacheRepository(new ArrayStore()), new RecordingRateLimiter($this->createStub(Repository::class)), new RecordingPaymentRepository());

        $this->expectException(InvalidAlbyWebhookPayloadException::class);

        $action->execute('"just a string"', 'id', 't', 's');
    }

    /**
     * The pack and the quota unlock arrive through the same webhook, and only
     * the memo distinguishes them.
     */
    public function test_a_settled_pack_grants_credit_to_its_buyer(): void
    {
        $packInvoice = new StubPackInvoice();
        $packInvoice->identities['deadbeef'] = 'npub-of-buyer';
        $credits = new StubPremiumCredits();
        $rate = new RecordingRateLimiter($this->createStub(Repository::class));

        $action = new AlbySettleWebhookAction(
            'secret',
            new CacheRepository(new ArrayStore()),
            $rate,
            new RecordingPaymentRepository(),
            $packInvoice,
            $credits,
            20,
            $this->createStub(LoggerInterface::class),
        );

        $action->execute($this->packPayload('SETTLED'), 'id', 't', 's');

        self::assertSame(
            [['npub' => 'npub-of-buyer', 'hash' => 'hash', 'messages' => 20]],
            $credits->grants,
        );
        // A pack buys credit, not a reset of the free quota.
        self::assertSame([], $rate->cleared);
    }

    public function test_an_unsettled_pack_grants_nothing(): void
    {
        $packInvoice = new StubPackInvoice();
        $packInvoice->identities['deadbeef'] = 'npub-of-buyer';
        $credits = new StubPremiumCredits();

        $action = new AlbySettleWebhookAction(
            'secret',
            new CacheRepository(new ArrayStore()),
            new RecordingRateLimiter($this->createStub(Repository::class)),
            new RecordingPaymentRepository(),
            $packInvoice,
            $credits,
            20,
            $this->createStub(LoggerInterface::class),
        );

        $action->execute($this->packPayload('CREATED'), 'id', 't', 's');

        self::assertSame([], $credits->grants);
    }

    private function packPayload(string $state): string
    {
        return json_encode([
            'payment_hash' => 'hash',
            'type' => 'incoming',
            'state' => $state,
            'memo' => InvoiceMemo::forPremiumPack('deadbeef'),
            'amount' => 500,
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * One place to build the action, so a change to its collaborators does not
     * have to be repeated across every case.
     */
    private function action(
        string $secret,
        Repository $cache,
        RateLimiter $rate,
        PaymentRepositoryInterface $repo,
    ): AlbySettleWebhookAction {
        return new AlbySettleWebhookAction(
            $secret,
            $cache,
            $rate,
            $repo,
            new StubPackInvoice(),
            new StubPremiumCredits(),
            20,
            $this->createStub(LoggerInterface::class),
        );
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

final class StubPackInvoice implements PremiumPackInvoiceInterface
{
    /** @var array<string, string> */
    public array $identities = [];

    public function issueFor(string $npub): array
    {
        return [];
    }

    public function identityFor(string $reference): ?string
    {
        return $this->identities[$reference] ?? null;
    }
}

final class StubPremiumCredits implements PremiumCreditsInterface
{
    /** @var list<array{npub: string, hash: string, messages: int}> */
    public array $grants = [];

    public function balanceFor(string $npub): int
    {
        return 0;
    }

    public function grantPack(string $npub, string $paymentHash, int $messages): void
    {
        $this->grants[] = ['npub' => $npub, 'hash' => $paymentHash, 'messages' => $messages];
    }

    public function spendOne(string $npub): bool
    {
        return false;
    }
}

