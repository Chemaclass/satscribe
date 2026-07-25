<?php

declare(strict_types=1);

namespace Modules\Payment\Application;

use Modules\Payment\Domain\AlbyClientInterface;
use Modules\Payment\Domain\ConfirmPremiumPackActionInterface;
use Modules\Payment\Domain\PremiumCreditsInterface;
use Modules\Payment\Domain\PremiumPackInvoiceInterface;
use Psr\Log\LoggerInterface;

final readonly class ConfirmPremiumPackAction implements ConfirmPremiumPackActionInterface
{
    public function __construct(
        private AlbyClientInterface $albyClient,
        private PremiumPackInvoiceInterface $packInvoice,
        private PremiumCreditsInterface $credits,
        private int $packMessages,
        private LoggerInterface $logger,
    ) {
    }

    public function execute(string $paymentHash, string $npub): bool
    {
        $owner = $this->packInvoice->identityForPaymentHash($paymentHash);

        // Only the invoice minted for this visitor may credit them. Without
        // this any settled hash — someone else's, or one read off an explorer —
        // would buy a pack for whoever quoted it.
        if ($owner !== $npub || $npub === '') {
            return $this->albyClient->isInvoicePaid($paymentHash);
        }

        if (!$this->albyClient->isInvoicePaid($paymentHash)) {
            return false;
        }

        // Idempotent on the hash, so the webhook granting first changes nothing.
        $this->credits->grantPack($npub, $paymentHash, $this->packMessages);

        $this->logger->info('Premium pack confirmed by the buyer', ['payment_hash' => $paymentHash]);

        return true;
    }
}
