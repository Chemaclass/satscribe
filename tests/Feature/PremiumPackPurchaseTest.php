<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Payment\Domain\AlbyClientInterface;
use Modules\Payment\Domain\Data\InvoiceData;
use Modules\Payment\Domain\PremiumCreditsInterface;
use Tests\TestCase;

/**
 * The whole purchase, through the routes a browser uses: quote a pack, settle
 * it, and have the credit appear. Real sats pay for this, so buying someone
 * else's invoice and buying the same one twice are both pinned.
 */
final class PremiumPackPurchaseTest extends TestCase
{
    use RefreshDatabase;

    private const NPUB = 'a1b2c3d4e5f60718293a4b5c6d7e8f90a1b2c3d4e5f60718293a4b5c6d7e8f90';
    private const HASH = 'pack-payment-hash';

    public function test_buying_without_a_login_is_refused(): void
    {
        $this->fakeAlby(paid: false);

        $this->postJson(route('api.premium.buy'))
            ->assertStatus(401)
            ->assertJsonPath('error', 'Log in with Nostr first, so your credit follows you between devices.');
    }

    public function test_buying_quotes_an_invoice_for_the_configured_price(): void
    {
        $alby = $this->fakeAlby(paid: false);

        $response = $this->asBuyer()->postJson(route('api.premium.buy'));

        $response->assertStatus(200);
        $response->assertJsonPath('invoice.payment_request', 'lnbc1invoice');
        $response->assertJsonPath('pack_messages', config_int('services.premium.pack_messages'));
        self::assertSame(config_int('services.premium.pack_sats'), $alby->lastInvoice?->amount);
    }

    public function test_an_unpaid_invoice_grants_nothing(): void
    {
        $this->fakeAlby(paid: false);
        $this->asBuyer()->postJson(route('api.premium.buy'));

        $this->asBuyer()->getJson(route('api.premium.status', self::HASH))
            ->assertStatus(200)
            ->assertJsonPath('paid', false)
            ->assertJsonPath('balance', 0);
    }

    public function test_a_settled_invoice_grants_the_pack(): void
    {
        $this->fakeAlby(paid: true);
        $this->asBuyer()->postJson(route('api.premium.buy'));

        $this->asBuyer()->getJson(route('api.premium.status', self::HASH))
            ->assertStatus(200)
            ->assertJsonPath('paid', true)
            ->assertJsonPath('balance', config_int('services.premium.pack_messages'));
    }

    public function test_polling_twice_grants_only_one_pack(): void
    {
        $this->fakeAlby(paid: true);
        $this->asBuyer()->postJson(route('api.premium.buy'));

        $this->asBuyer()->getJson(route('api.premium.status', self::HASH));
        $this->asBuyer()->getJson(route('api.premium.status', self::HASH));
        $this->asBuyer()->getJson(route('api.premium.status', self::HASH));

        self::assertSame(
            config_int('services.premium.pack_messages'),
            app(PremiumCreditsInterface::class)->balanceFor(self::NPUB),
        );
    }

    /**
     * Otherwise quoting a settled hash read off an explorer would buy a pack
     * for whoever quoted it.
     */
    public function test_someone_elses_settled_invoice_grants_nothing(): void
    {
        $this->fakeAlby(paid: true);
        $this->asBuyer()->postJson(route('api.premium.buy'));

        $stranger = str_repeat('b', 64);

        $this->withSession(['nostr_pubkey' => $stranger])
            ->getJson(route('api.premium.status', self::HASH))
            ->assertStatus(200)
            ->assertJsonPath('balance', 0);

        self::assertSame(0, app(PremiumCreditsInterface::class)->balanceFor($stranger));
    }

    public function test_the_balance_endpoint_reports_the_pack_terms(): void
    {
        $this->fakeAlby(paid: false);

        $this->getJson(route('api.premium.balance'))
            ->assertStatus(200)
            ->assertJsonPath('balance', 0)
            ->assertJsonPath('pack_sats', config_int('services.premium.pack_sats'));
    }

    private function asBuyer(): self
    {
        return $this->withSession(['nostr_pubkey' => self::NPUB]);
    }

    private function fakeAlby(bool $paid): RecordingPackAlbyClient
    {
        $alby = new RecordingPackAlbyClient($paid, self::HASH);

        $this->app->instance(AlbyClientInterface::class, $alby);

        return $alby;
    }
}

final class RecordingPackAlbyClient implements AlbyClientInterface
{
    public ?InvoiceData $lastInvoice = null;

    public function __construct(private readonly bool $paid, private readonly string $hash)
    {
    }

    public function isConnectionValid(): bool
    {
        return true;
    }

    public function createInvoice(InvoiceData $invoice): array
    {
        $this->lastInvoice = $invoice;

        return [
            'payment_hash' => $this->hash,
            'payment_request' => 'lnbc1invoice',
            'created_at' => now()->toIso8601String(),
            'expiry' => $invoice->expiry,
            'id' => $this->hash,
            'r_hash' => $this->hash,
        ];
    }

    public function isInvoicePaid(string $hash): bool
    {
        return $this->paid;
    }

    public function getInvoice(string $hash): array
    {
        return [];
    }
}
