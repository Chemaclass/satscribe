<?php

declare(strict_types=1);

namespace Modules\Payment\Domain;

interface PremiumCreditsInterface
{
    /**
     * Premium messages the identity has left.
     */
    public function balanceFor(string $npub): int;

    /**
     * Adds a purchased pack. The payment hash makes this idempotent: Alby can
     * deliver the same settlement more than once, and the visitor's own status
     * poll confirms it too, so the grant has to survive being asked for twice.
     */
    public function grantPack(string $npub, string $paymentHash, int $messages): void;

    /**
     * Consumes one message. Returns false when the balance is empty, so the
     * caller can answer with an invoice instead of calling a paid provider.
     */
    public function spendOne(string $npub): bool;
}
