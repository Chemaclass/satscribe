<?php

declare(strict_types=1);

namespace Modules\Payment\Application;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Modules\Payment\Domain\AlbyClientInterface;
use Modules\Payment\Domain\Data\InvoiceData;
use Modules\Payment\Domain\Data\InvoiceMemo;
use Modules\Payment\Domain\PremiumPackInvoiceInterface;

use function is_string;

final readonly class PremiumPackInvoice implements PremiumPackInvoiceInterface
{
    /**
     * The reference-to-identity mapping has to outlive the invoice by a wide
     * margin: a buyer who settles near the deadline is confirmed after it, and
     * a mapping that expired first would take their credit with it. This is the
     * same failure the paywall mapping had.
     */
    private const MAPPING_TTL_SECONDS = 86400;

    public function __construct(
        private AlbyClientInterface $albyClient,
        private CacheRepository $cache,
        private int $packSats,
        private int $packMessages,
        private int $expirySeconds,
    ) {
    }

    public function issueFor(string $npub): array
    {
        $reference = InvoiceMemo::reference($npub);

        $this->cache->put($this->keyFor($reference), $npub, self::MAPPING_TTL_SECONDS);

        $invoice = $this->albyClient->createInvoice(new InvoiceData(
            amount: $this->packSats,
            memo: InvoiceMemo::forPremiumPack($reference),
            expiry: $this->expirySeconds,
        ));

        // Remembered against the payment hash as well, so the buyer's own
        // status poll can prove the invoice is theirs before granting.
        $this->cache->put($this->hashKeyFor($invoice['payment_hash']), $npub, self::MAPPING_TTL_SECONDS);

        return $invoice;
    }

    public function identityForPaymentHash(string $paymentHash): ?string
    {
        $npub = $this->cache->get($this->hashKeyFor($paymentHash));

        return is_string($npub) && $npub !== '' ? $npub : null;
    }

    public function identityFor(string $reference): ?string
    {
        $npub = $this->cache->get($this->keyFor($reference));

        return is_string($npub) && $npub !== '' ? $npub : null;
    }

    public function packMessages(): int
    {
        return $this->packMessages;
    }

    private function keyFor(string $reference): string
    {
        return 'premium_pack_identity:' . $reference;
    }

    private function hashKeyFor(string $paymentHash): string
    {
        return 'premium_pack_hash:' . $paymentHash;
    }
}
