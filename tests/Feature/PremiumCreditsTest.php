<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PremiumCreditEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Payment\Domain\PremiumCreditsInterface;
use Tests\TestCase;

/**
 * Real sats buy these credits, so the two ways to lose money both have to be
 * closed: granting the same payment twice, and spending a balance that is not
 * there.
 */
final class PremiumCreditsTest extends TestCase
{
    use RefreshDatabase;

    private const NPUB = 'a1b2c3d4e5f60718293a4b5c6d7e8f90a1b2c3d4e5f60718293a4b5c6d7e8f90';
    private const HASH = 'payment-hash-1';

    public function test_a_new_identity_has_no_credit(): void
    {
        self::assertSame(0, $this->credits()->balanceFor(self::NPUB));
    }

    public function test_a_purchase_grants_the_pack(): void
    {
        $this->credits()->grantPack(self::NPUB, self::HASH, 20);

        self::assertSame(20, $this->credits()->balanceFor(self::NPUB));
    }

    /**
     * Alby can redeliver a settlement, and the payer's own status poll confirms
     * it as well, so the same hash must never grant twice.
     */
    public function test_the_same_payment_grants_only_once(): void
    {
        $this->credits()->grantPack(self::NPUB, self::HASH, 20);
        $this->credits()->grantPack(self::NPUB, self::HASH, 20);
        $this->credits()->grantPack(self::NPUB, self::HASH, 20);

        self::assertSame(20, $this->credits()->balanceFor(self::NPUB));
    }

    public function test_spending_reduces_the_balance(): void
    {
        $this->credits()->grantPack(self::NPUB, self::HASH, 3);

        self::assertTrue($this->credits()->spendOne(self::NPUB));
        self::assertSame(2, $this->credits()->balanceFor(self::NPUB));
    }

    public function test_spending_an_empty_balance_is_refused(): void
    {
        self::assertFalse($this->credits()->spendOne(self::NPUB));
        self::assertSame(0, $this->credits()->balanceFor(self::NPUB));
    }

    public function test_the_balance_cannot_be_driven_negative(): void
    {
        $this->credits()->grantPack(self::NPUB, self::HASH, 2);

        self::assertTrue($this->credits()->spendOne(self::NPUB));
        self::assertTrue($this->credits()->spendOne(self::NPUB));
        self::assertFalse($this->credits()->spendOne(self::NPUB));

        self::assertSame(0, $this->credits()->balanceFor(self::NPUB));
    }

    public function test_credit_is_scoped_to_one_identity(): void
    {
        $other = str_repeat('b', 64);

        $this->credits()->grantPack(self::NPUB, self::HASH, 5);

        self::assertSame(5, $this->credits()->balanceFor(self::NPUB));
        self::assertSame(0, $this->credits()->balanceFor($other));
        self::assertFalse($this->credits()->spendOne($other));
    }

    public function test_every_movement_is_recorded(): void
    {
        $this->credits()->grantPack(self::NPUB, self::HASH, 2);
        $this->credits()->spendOne(self::NPUB);

        self::assertSame(1, PremiumCreditEntry::where('reason', PremiumCreditEntry::REASON_PURCHASE)->count());
        self::assertSame(1, PremiumCreditEntry::where('reason', PremiumCreditEntry::REASON_SPEND)->count());
    }

    private function credits(): PremiumCreditsInterface
    {
        return app(PremiumCreditsInterface::class);
    }
}
