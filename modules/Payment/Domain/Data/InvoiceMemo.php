<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Data;

use function sprintf;

/**
 * Two products settle through the same Alby webhook — an unlock of the free
 * quota, and a pack of premium messages — and the memo is the only field that
 * comes back with the payment. Both the issuing and the settling side read this
 * one class, so they cannot drift into disagreeing about which product a
 * payment was for.
 *
 * The reference is a short digest of the buyer's identity rather than the
 * identity itself: memos are short, and an npub is 64 characters.
 */
final readonly class InvoiceMemo
{
    private const PACK_PREFIX = 'pk';

    public static function forPaywall(string $reference): string
    {
        return sprintf('Zap to keep Satscribe alive ⚡️ #%s', $reference);
    }

    public static function forPremiumPack(string $reference): string
    {
        return sprintf('Satscribe premium pack ⚡️ #%s%s', self::PACK_PREFIX, $reference);
    }

    public static function reference(string $identity): string
    {
        return substr(md5($identity), 0, 8);
    }

    /**
     * The pack reference carried by a memo, or null when it is not a pack.
     * Checked before the paywall pattern, which matches a bare 8-character
     * digest and would otherwise be ambiguous.
     */
    public static function premiumPackReference(string $memo): ?string
    {
        return preg_match('/#' . self::PACK_PREFIX . '([a-f0-9]{8})/', $memo, $m) === 1
            ? $m[1]
            : null;
    }

    public static function paywallReference(string $memo): ?string
    {
        if (self::premiumPackReference($memo) !== null) {
            return null;
        }

        return preg_match('/#([a-f0-9]{8})/', $memo, $m) === 1 ? $m[1] : null;
    }
}
