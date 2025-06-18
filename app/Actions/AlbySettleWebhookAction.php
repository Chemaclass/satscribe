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
        $verifiedPayload = $this->verifySignature(
            payload: $payload,
            svixId: $svixId,
            svixTimestamp: $svixTimestamp,
            svixSignature: $svixSignature,
        );

        $this->logger->info('Webhook payload', ['payload' => $verifiedPayload->toArray()]);

        if ($verifiedPayload->type === 'incoming'
            && $verifiedPayload->state === 'SETTLED'
        ) {
            $this->handleInvoiceSettled($verifiedPayload);
        } else {
            $this->logger->info('Unhandled webhook payload', ['payload' => $verifiedPayload->toArray()]);
        }
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
            $data = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);
            return AlbySettleWebhookPayload::fromArray($data);
        } catch (Throwable $e) {
            $this->logger->warning('Webhook verification failed', ['error' => $e->getMessage()]);
            throw new InvalidAlbyWebhookSignatureException();
        }
    }

    private function handleInvoiceSettled(AlbySettleWebhookPayload $payload): void
    {
        $invoiceHash = $payload->paymentHash;
        $memo = $payload->memo;

        $this->logger->info('Invoice settled', ['$memo' => $memo]);

        $hash = $this->extractShortHash($memo);
        $trackingId = null;
        if ($hash !== null) {
            $trackingId = $this->cache->pull(IpRateLimiter::createCacheKey($hash));
            if ($trackingId) {
                $this->rateLimiter->clear(IpRateLimiter::createRateLimitKey($trackingId));
                $this->logger->info('Rate limit cleared for tracking', ['trackingId' => $trackingId]);
            } else {
                $this->logger->warning('No tracking ID found for hash', ['shortHash' => $hash]);
            }
        }

        $this->paymentRepository->create([
            'tracking_id' => $trackingId,
            'payment_hash' => $invoiceHash,
            'memo' => $memo,
            'amount' => $payload->amount,
        ]);

        $this->logger->info('Invoice settled', [
            'payment_hash' => $invoiceHash,
            'amount' => $payload->amount,
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
