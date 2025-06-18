<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\AlbySettleWebhookPayload;
use App\Exceptions\InvalidAlbyWebhookSignatureException;
use App\Http\Middleware\IpRateLimiter;
use App\Repositories\PaymentRepositoryInterface;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Psr\Log\LoggerInterface;
use Svix\Webhook;
use Throwable;

final readonly class AlbySettleWebhookAction
{
    private Webhook $webhook;

    public function __construct(
        private string $webhookSecret,
        private CacheRepository $cache,
        private RateLimiter $rateLimiter,
        private PaymentRepositoryInterface $paymentRepository,
        private LoggerInterface $logger,
    ) {
        $this->webhook = new Webhook($this->webhookSecret);
    }

    public function execute(
        string $payload,
        string $svixId,
        string $svixTimestamp,
        string $svixSignature,
    ): void {
        $verified = $this->verifySignature($payload, $svixId, $svixTimestamp, $svixSignature);

        $this->logger->info('Webhook payload', ['payload' => $verified->toArray()]);

        if ($verified->type !== 'incoming') {
            $this->logger->info('Unhandled webhook payload', ['payload' => $verified->toArray()]);
            return;
        }

        $verified->state === 'SETTLED'
            ? $this->handleInvoice($verified, isFailure: false)
            : $this->handleInvoice($verified, isFailure: true);
    }

    private function verifySignature(
        string $payload,
        string $svixId,
        string $svixTimestamp,
        string $svixSignature,
    ): AlbySettleWebhookPayload {
        if ($this->webhookSecret === '') {
            $this->logger->warning('Webhook secret not configured');
            throw new InvalidAlbyWebhookSignatureException();
        }

        try {
            $this->webhook->verify($payload, [
                'svix-id' => $svixId,
                'svix-timestamp' => $svixTimestamp,
                'svix-signature' => $svixSignature,
            ]);

            $this->logger->info('Webhook successfully verified');

            return AlbySettleWebhookPayload::fromArray(
                json_decode($payload, true, flags: JSON_THROW_ON_ERROR)
            );
        } catch (Throwable $e) {
            $this->logger->warning('Webhook verification failed', ['error' => $e->getMessage()]);
            throw new InvalidAlbyWebhookSignatureException();
        }
    }

    private function handleInvoice(AlbySettleWebhookPayload $payload, bool $isFailure): void
    {
        $hash = $this->extractShortHash($payload->memo);
        $trackingId = null;
        $chatId = null;

        if ($hash !== null) {
            $cached = $this->cache->pull(IpRateLimiter::createCacheKey($hash));
            if (is_array($cached)) {
                $trackingId = $cached['tracking_id'] ?? null;
                $chatId = $cached['chat_id'] ?? null;
            } elseif ($cached) {
                $trackingId = $cached;
            }

            if (! $cached) {
                $this->logger->warning('No tracking data found for hash', ['shortHash' => $hash]);
            }

            if ($trackingId && ! $isFailure) {
                $this->rateLimiter->clear(IpRateLimiter::createRateLimitKey($trackingId));
                $this->logger->info('Rate limit cleared', ['trackingId' => $trackingId]);
            }
        }

        $this->paymentRepository->create([
            'tracking_id' => $trackingId,
            'chat_id' => $chatId,
            'payment_hash' => $payload->paymentHash,
            'memo' => $payload->memo,
            'amount' => $payload->amount,
            'status' => $payload->state,
            'failure_reason' => $isFailure ? $payload->state : null,
        ]);

        $this->logger->info($isFailure ? 'Invoice failure stored' : 'Invoice settled', [
            'payment_hash' => $payload->paymentHash,
            'amount' => $payload->amount,
            'state' => $payload->state,
        ]);
    }

    private function extractShortHash(string $memo): ?string
    {
        if (preg_match('/#([a-f0-9]{8})/', $memo, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
