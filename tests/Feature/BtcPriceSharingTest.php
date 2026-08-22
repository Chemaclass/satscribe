<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blockchain\BlockchainServiceProvider;
use Modules\Blockchain\Domain\PriceServiceInterface;
use RuntimeException;
use Tests\TestCase;

/**
 * The price ticker is decoration on the base layout. It used to be shared from
 * the provider's boot(), which runs on every request including /up — a slow
 * Coingecko then stalled the health check the deploy waits on.
 */
final class BtcPriceSharingTest extends TestCase
{
    use RefreshDatabase;

    public function test_booting_the_provider_does_not_look_up_the_btc_price(): void
    {
        $priceService = new CountingPriceService();
        $this->app->instance(PriceServiceInterface::class, $priceService);

        (new BlockchainServiceProvider($this->app))->boot();

        self::assertSame(0, $priceService->calls);
    }

    public function test_prices_reach_the_layout_when_the_lookup_succeeds(): void
    {
        $this->app->instance(PriceServiceInterface::class, new FixedPriceService());

        $response = $this->get('/nostr');

        $response->assertOk();
        $response->assertSee('data-btc-price-item', escape: false);
        $response->assertSee('$60,123', escape: false);
        $response->assertSee('&euro;55,987', escape: false);
    }

    public function test_a_price_lookup_failure_does_not_break_the_page(): void
    {
        $this->app->instance(PriceServiceInterface::class, new FailingPriceService());

        $response = $this->get('/nostr');

        $response->assertOk();
        $response->assertDontSee('data-btc-price-item', escape: false);
    }
}

final class FailingPriceService implements PriceServiceInterface
{
    public function getCurrentBtcPriceUsd(): float
    {
        throw new RuntimeException('Failed to fetch current BTC price');
    }

    public function getCurrentBtcPriceEur(): float
    {
        throw new RuntimeException('Failed to fetch current BTC price');
    }

    public function getCurrentBtcPriceCny(): float
    {
        throw new RuntimeException('Failed to fetch current BTC price');
    }

    public function getCurrentBtcPriceGbp(): float
    {
        throw new RuntimeException('Failed to fetch current BTC price');
    }

    public function getBtcPriceUsdAt(int $timestamp): float
    {
        return 0.0;
    }

    public function getBtcPriceEurAt(int $timestamp): float
    {
        return 0.0;
    }
}

final class FixedPriceService implements PriceServiceInterface
{
    public function getCurrentBtcPriceUsd(): float
    {
        return 60123.45;
    }

    public function getCurrentBtcPriceEur(): float
    {
        return 55987.10;
    }

    public function getCurrentBtcPriceCny(): float
    {
        return 432100.99;
    }

    public function getCurrentBtcPriceGbp(): float
    {
        return 47555.55;
    }

    public function getBtcPriceUsdAt(int $timestamp): float
    {
        return 0.0;
    }

    public function getBtcPriceEurAt(int $timestamp): float
    {
        return 0.0;
    }
}

final class CountingPriceService implements PriceServiceInterface
{
    public int $calls = 0;

    public function getCurrentBtcPriceUsd(): float
    {
        ++$this->calls;

        return 1.0;
    }

    public function getCurrentBtcPriceEur(): float
    {
        ++$this->calls;

        return 2.0;
    }

    public function getCurrentBtcPriceCny(): float
    {
        ++$this->calls;

        return 3.0;
    }

    public function getCurrentBtcPriceGbp(): float
    {
        ++$this->calls;

        return 4.0;
    }

    public function getBtcPriceUsdAt(int $timestamp): float
    {
        return 0.0;
    }

    public function getBtcPriceEurAt(int $timestamp): float
    {
        return 0.0;
    }
}
