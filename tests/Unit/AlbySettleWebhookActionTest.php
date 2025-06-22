<?php

declare(strict_types=1);

namespace Svix;

class Webhook
{
    public array $calls = [];

    public function __construct(private readonly string $secret)
    {
    }

    public function verify(string $payload, array $headers): void
    {
        if ($this->secret === 'throw') {
            throw new \RuntimeException('invalid');
        }

        $this->calls[] = [$payload, $headers];
    }
}

namespace Illuminate\Cache;

class RateLimiter
{
    public array $cleared = [];

    public function clear(string $key): void
    {
        $this->cleared[] = $key;
    }
}

namespace Tests\Unit;

use App\Models\Payment;
use Illuminate\Contracts\Cache\Repository;
use Modules\Payment\Application\AlbySettleWebhookAction;
use Modules\Payment\Domain\Exception\InvalidAlbyWebhookSignatureException;
use Modules\Payment\Domain\Repository\PaymentRepositoryInterface;
use Modules\Shared\Infrastructure\Http\Middleware\IpRateLimiter;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class AlbySettleWebhookActionTest extends TestCase
{
    public function test_fails_when_secret_missing(): void
    {
        $cache = $this->createStub(Repository::class);
        $rate = new \Illuminate\Cache\RateLimiter();
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

        $rate = new \Illuminate\Cache\RateLimiter();

        $repo = new class() implements PaymentRepositoryInterface {
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
        ]);

        $action->execute($payload, 'id', 't', 's');

        $this->assertSame('hash', $repo->data['payment_hash']);
        $this->assertContains(IpRateLimiter::createRateLimitKey('track'), $rate->cleared);
        $this->assertContains('ln_invoice:deadbeef', $rate->cleared);
    }
}
