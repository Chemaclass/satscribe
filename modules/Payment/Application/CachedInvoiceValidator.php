<?php

declare(strict_types=1);

namespace Modules\Payment\Application;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Modules\Payment\Domain\AlbyClientInterface;
use Modules\Payment\Domain\CachedInvoiceValidatorInterface;
use Psr\Log\LoggerInterface;
use Throwable;

use function is_string;

final readonly class CachedInvoiceValidator implements CachedInvoiceValidatorInterface
{
    public function __construct(
        private AlbyClientInterface $albyClient,
        private LoggerInterface $logger,
        private CarbonInterface $now,
    ) {
    }

    /**
     * @param  array<string, mixed>|null  $cached
     */
    public function isValidCachedInvoice(?array $cached): bool
    {
        $hash = $cached['payment_hash'] ?? null;
        $request = $cached['payment_request'] ?? null;
        $createdAt = $cached['created_at'] ?? null;
        $expiry = $cached['expiry'] ?? null;

        // The cache is untyped storage, so a key being present says nothing
        // about its type: an unchecked created_at reached Carbon::parse() and
        // killed the request with a TypeError rather than being discarded.
        if (
            !is_string($hash) ||
            !is_string($request) ||
            !is_string($createdAt) ||
            !is_numeric($expiry)
        ) {
            $this->logger->warning('Invalid cached invoice structure', ['cached' => $cached]);

            return false;
        }

        $expiresAt = Carbon::parse($createdAt)->addSeconds((int) $expiry);
        $this->logger->debug('Cached invoice expiry', ['expires_at' => $expiresAt->toDateTimeString()]);

        if ($this->now->greaterThanOrEqualTo($expiresAt)) {
            $this->logger->debug('Cached invoice expired');

            return false;
        }

        try {
            if ($this->albyClient->isInvoicePaid($hash)) {
                $this->logger->info('Invoice already paid', ['payment_hash' => $hash]);

                return false;
            }
        } catch (Throwable $e) {
            $this->logger->warning('Invoice verification failed', ['error' => $e->getMessage()]);

            return false;
        }

        return true;
    }
}
