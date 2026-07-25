<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Exception;

use RuntimeException;

use function sprintf;

/**
 * Raised instead of calling a paid provider, so the caller can offer an invoice.
 * Separate from a login failure because the two need different answers: one is
 * "sign in", the other is "buy a pack".
 */
final class PremiumCreditRequired extends RuntimeException
{
    public static function notLoggedIn(): self
    {
        return new self('Premium models need a Nostr login, so your credit follows you between devices.');
    }

    public static function noBalance(int $packSats, int $packMessages): self
    {
        return new self(sprintf(
            'You have no premium messages left. %d sats buys %d more.',
            $packSats,
            $packMessages,
        ));
    }
}
