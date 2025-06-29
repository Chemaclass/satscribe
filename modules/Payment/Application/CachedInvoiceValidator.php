<?php

declare(strict_types=1);

namespace Modules\Payment\Application;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Modules\Payment\Domain\AlbyClientInterface;
use Modules\Payment\Domain\CachedInvoiceValidatorInterface;
use Psr\Log\LoggerInterface;
use Throwable;

use function is_array;

final readonly class CachedInvoiceValidator implements CachedInvoiceValidatorInterface
{
    public function __construct(
        private AlbyClientInterface $albyClient,
        private LoggerInterface $logger,
        private CarbonInterface $now,
    ) {
    }

    public function isValidCachedInvoice(?array $cached): bool
    {
        if (
            !is_array($cached) ||
            !isset($cached['payment_hash'], $cached['payment_request'], $cached['created_at'], $cached['expiry'])
        ) {
            $this->logger->warning('Invalid cached invoice structure', ['cached' => $cached]);

            return false;
        }

        $expiresAt = Carbon::parse($cached['created_at'])->addSeconds((int) $cached['expiry']);
        $this->logger->debug('Cached invoice expiry', ['expires_at' => $expiresAt->toDateTimeString()]);

        if ($this->now->greaterThanOrEqualTo($expiresAt)) {
            $this->logger->debug('Cached invoice expired');

            return false;
        }

        try {
            if ($this->albyClient->isInvoicePaid($cached['payment_hash'])) {
                $this->logger->info('Invoice already paid', ['payment_hash' => $cached['payment_hash']]);

                return false;
            }
        } catch (Throwable $e) {
            $this->logger->warning('Invoice verification failed', ['error' => $e->getMessage()]);

            return false;
        }

        return true;
    }
}
