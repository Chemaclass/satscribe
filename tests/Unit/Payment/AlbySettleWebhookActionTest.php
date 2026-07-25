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
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Cache\Repository;
use Modules\Payment\Application\AlbySettleWebhookAction;
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
        $cache = $this->createStub(Repository::class);
        $cache->method('pull')->willReturn(['tracking_id' => 'track']);

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
        $this->assertContains('ln_invoice:deadbeef', $rate->cleared);
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
