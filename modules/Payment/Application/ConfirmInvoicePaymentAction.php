<?php

declare(strict_types=1);

namespace Modules\Payment\Application;

use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Modules\Payment\Domain\AlbyClientInterface;
use Modules\Payment\Domain\ConfirmInvoicePaymentActionInterface;
use Modules\Shared\Domain\RateLimit\RateLimitKeys;
use Psr\Log\LoggerInterface;

use function is_array;

final readonly class ConfirmInvoicePaymentAction implements ConfirmInvoicePaymentActionInterface
{
    public function __construct(
        private AlbyClientInterface $albyClient,
        private CacheRepository $cache,
        private RateLimiter $rateLimiter,
        private LoggerInterface $logger,
    ) {
    }

    public function execute(string $identifier, string $trackingId): bool
    {
        $invoiceKey = RateLimitKeys::forInvoice(RateLimitKeys::shortHashFor($trackingId));
        $cached = $this->cache->get($invoiceKey);
        $paymentHash = is_array($cached) ? ($cached['payment_hash'] ?? null) : null;

        // Only the invoice minted for this visitor may lift their paywall.
        // Without this check any known-settled payment hash would do, and the
        // limit could be cleared without paying for it.
        if ($paymentHash !== $identifier) {
            return $this->albyClient->isInvoicePaid($identifier);
        }

        if (!$this->albyClient->isInvoicePaid($identifier)) {
            return false;
        }

        $this->rateLimiter->clear(RateLimitKeys::forTrackingId($trackingId));

        // Drop the invoice so the same payment cannot clear the limit twice.
        $this->cache->forget($invoiceKey);

        $this->logger->info('Paywall lifted after confirming payment', [
            'payment_hash' => $identifier,
        ]);

        return true;
    }
}
