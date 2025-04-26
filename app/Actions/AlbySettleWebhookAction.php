<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\InvalidAlbyWebhookSignatureException;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Psr\Log\LoggerInterface;
use Svix\Webhook;

final class AlbySettleWebhookAction
{
    private Webhook $webhook;

    public function __construct(
        private readonly string $webhookSecret,
        private readonly CacheRepository $cache,
        private readonly RateLimiter $rateLimiter,
        private readonly LoggerInterface $logger,
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

        $this->logger->info('Webhook payload', ['payload' => $verifiedPayload]);

        if (($verifiedPayload['type'] ?? '') === 'incoming' && ($verifiedPayload['state'] ?? '') === 'SETTLED') {
            $this->handleInvoiceSettled($verifiedPayload);
        } else {
            $this->logger->info('Unhandled webhook payload', ['payload' => $verifiedPayload]);
        }
    }

    private function verifySignature(
        string $payload,
        string $svixId,
        string $svixTimestamp,
        string $svixSignature,
    ): array {
        if (empty($this->webhookSecret)) {
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
            return json_decode($payload, true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            $this->logger->warning('Webhook verification failed', ['error' => $e->getMessage()]);
            throw new InvalidAlbyWebhookSignatureException();
        }
    }

    private function handleInvoiceSettled(array $payload): void
    {
        $invoiceHash = $payload['payment_hash'] ?? null;
        $memo = $payload['memo'] ?? '';

        $this->logger->info('Invoice settled', ['$memo' => $memo]);

        if (str_contains($memo, '#')) {
            $shortHash = $this->extractShortHash($memo);
            if ($shortHash) {
                $ip = $this->cache->pull('invoice_ip_mapping_'.$shortHash);

                if ($ip) {
                    $key = 'ip_rate_limit_'.$ip;
                    $this->rateLimiter->clear($key);
                    $this->logger->info('Rate limit cleared for IP', ['ip' => $ip]);
                } else {
                    $this->logger->warning('No IP found for hash', ['shortHash' => $shortHash]);
                }
            }
        }

        $this->logger->info('Invoice settled', [
            'payment_hash' => $invoiceHash,
            'amount' => $payload['amount'] ?? 0,
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
