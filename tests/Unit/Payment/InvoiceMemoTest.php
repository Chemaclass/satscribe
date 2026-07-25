<?php

declare(strict_types=1);

namespace Tests\Unit\Payment;

use Modules\Payment\Domain\Data\InvoiceMemo;
use PHPUnit\Framework\TestCase;

/**
 * The memo is the only thing that comes back with a settled payment, so telling
 * the two products apart from it decides which one the buyer gets.
 */
final class InvoiceMemoTest extends TestCase
{
    private const REFERENCE = 'deadbeef';

    public function test_a_paywall_memo_reads_back_as_a_paywall_reference(): void
    {
        $memo = InvoiceMemo::forPaywall(self::REFERENCE);

        self::assertSame(self::REFERENCE, InvoiceMemo::paywallReference($memo));
        self::assertNull(InvoiceMemo::premiumPackReference($memo));
    }

    public function test_a_pack_memo_reads_back_as_a_pack_reference(): void
    {
        $memo = InvoiceMemo::forPremiumPack(self::REFERENCE);

        self::assertSame(self::REFERENCE, InvoiceMemo::premiumPackReference($memo));
    }

    /**
     * The paywall pattern matches a bare eight-character digest, which a pack
     * memo also contains. Without the pack check taking precedence, buying a
     * pack would be settled as a quota unlock and the credit never granted.
     */
    public function test_a_pack_memo_is_not_mistaken_for_a_paywall_one(): void
    {
        $memo = InvoiceMemo::forPremiumPack(self::REFERENCE);

        self::assertNull(InvoiceMemo::paywallReference($memo));
    }

    public function test_an_unrelated_memo_matches_neither(): void
    {
        self::assertNull(InvoiceMemo::paywallReference('Thanks for the coffee'));
        self::assertNull(InvoiceMemo::premiumPackReference('Thanks for the coffee'));
    }

    public function test_the_reference_is_stable_and_hides_the_identity(): void
    {
        $npub = str_repeat('a', 64);

        $reference = InvoiceMemo::reference($npub);

        self::assertSame($reference, InvoiceMemo::reference($npub));
        self::assertNotSame($reference, InvoiceMemo::reference(str_repeat('b', 64)));
        self::assertStringNotContainsString($reference, $npub);
    }
}
