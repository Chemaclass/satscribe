<?php

declare(strict_types=1);

namespace Modules\Payment\Application;

use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Modules\Payment\Domain\Data\AlbySettleWebhookPayload;
use Modules\Payment\Domain\Exception\InvalidAlbyWebhookPayloadException;
use Modules\Payment\Domain\Exception\InvalidAlbyWebhookSignatureException;
use Modules\Payment\Domain\Repository\PaymentRepositoryInterface;
use Modules\Shared\Domain\RateLimit\RateLimitKeys;
use Psr\Log\LoggerInterface;
use Svix\Webhook;
use Throwable;

use function is_array;
use function is_string;

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

        $this->logger->info('Webhook payload received', ['payload' => $verified->toArray()]);

        if ($verified->type !== 'incoming') {
            $this->logger->warning('Unhandled webhook type', ['type' => $verified->type]);
            return;
        }

        $this->handleInvoice($verified, $verified->state !== 'SETTLED');
    }

    private function verifySignature(
        string $payload,
        string $svixId,
        string $svixTimestamp,
        string $svixSignature,
    ): AlbySettleWebhookPayload {
        if ($this->webhookSecret === '') {
            $this->logger->warning('Webhook secret is not configured');
            throw new InvalidAlbyWebhookSignatureException();
        }

        try {
            $this->webhook->verify($payload, [
                'svix-id' => $svixId,
                'svix-timestamp' => $svixTimestamp,
                'svix-signature' => $svixSignature,
            ]);

            $this->logger->info('Webhook signature successfully verified');

            $data = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);

            if (!is_array($data)) {
                throw InvalidAlbyWebhookPayloadException::malformed('body', 'a JSON object');
            }

            /** @var array<string, mixed> $data */
            return AlbySettleWebhookPayload::fromArray($data);
        } catch (Throwable $e) {
            $this->logger->warning('Webhook signature verification failed', ['error' => $e->getMessage()]);
            throw new InvalidAlbyWebhookSignatureException();
        }
    }

    private function handleInvoice(AlbySettleWebhookPayload $payload, bool $isFailure): void
    {
        $trackingId = null;
        $shortHash = $this->extractShortHash($payload->memo);

        if ($shortHash === null) {
            $this->logger->warning('No hash found in memo', ['memo' => $payload->memo]);
        } else {
            $cached = $this->cache->pull(RateLimitKeys::forInvoiceTrackingMapping($shortHash));

            // The cache writer stores ['tracking_id' => string]; the bare-string
            // branch tolerates entries written by an earlier format.
            $rawTrackingId = is_array($cached) ? ($cached['tracking_id'] ?? null) : $cached;
            $trackingId = is_string($rawTrackingId) ? $rawTrackingId : null;

            if ($cached !== null) {
                $this->logger->info('Tracking data found for short hash', ['shortHash' => $shortHash]);
            } else {
                $this->logger->warning('No tracking data found for short hash', ['shortHash' => $shortHash]);
            }

            if ($trackingId && !$isFailure) {
                $cacheKey = RateLimitKeys::forTrackingId($trackingId);
                $this->rateLimiter->clear($cacheKey);

                $this->logger->info('Rate limit cleared for tracking ID', [
                    'trackingId' => $trackingId,
                    'invoiceCacheKey' => $cacheKey,
                ]);
            }
        }

        $invoiceCacheKey = "ln_invoice:{$shortHash}";

        $this->rateLimiter->clear($invoiceCacheKey);

        $this->paymentRepository->create([
            'tracking_id' => $trackingId,
            'payment_hash' => $payload->paymentHash,
            'memo' => $payload->memo,
            'amount' => $payload->amount,
            'status' => $payload->state,
            'failure_reason' => $isFailure ? $payload->state : null,
        ]);

        $this->logger->info(
            $isFailure ? 'Invoice failure stored' : 'Invoice settled',
            [
                'payment_hash' => $payload->paymentHash,
                'amount' => $payload->amount,
                'state' => $payload->state,
                'invoiceCacheKey' => $invoiceCacheKey,
            ],
        );
    }

    private function extractShortHash(string $memo): ?string
    {
        return preg_match('/#([a-f0-9]{8})/', $memo, $matches) ? $matches[1] : null;
    }
}
