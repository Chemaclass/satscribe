<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\View;
use Modules\Blockchain\BlockchainServiceProvider;
use Modules\Blockchain\Domain\PriceServiceInterface;
use RuntimeException;
use Tests\TestCase;

/**
 * The price ticker is shared from the provider's boot(), which runs on every
 * request. Coingecko rate-limits its free tier aggressively, so an unguarded
 * failure there would turn a missing price badge into a site-wide 500.
 */
final class BtcPriceSharingTest extends TestCase
{
    public function test_a_price_lookup_failure_does_not_break_the_request(): void
    {
        $this->app->instance(PriceServiceInterface::class, new FailingPriceService());

        (new BlockchainServiceProvider($this->app))->boot();

        self::assertSame(0.0, View::shared('btcPriceUsd'));
        self::assertSame(0.0, View::shared('btcPriceEur'));
        self::assertSame(0.0, View::shared('btcPriceCny'));
        self::assertSame(0.0, View::shared('btcPriceGbp'));
    }

    public function test_prices_are_shared_when_the_lookup_succeeds(): void
    {
        $this->app->instance(PriceServiceInterface::class, new FixedPriceService());

        (new BlockchainServiceProvider($this->app))->boot();

        self::assertSame(1.0, View::shared('btcPriceUsd'));
        self::assertSame(2.0, View::shared('btcPriceEur'));
        self::assertSame(3.0, View::shared('btcPriceCny'));
        self::assertSame(4.0, View::shared('btcPriceGbp'));
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
        return 1.0;
    }

    public function getCurrentBtcPriceEur(): float
    {
        return 2.0;
    }

    public function getCurrentBtcPriceCny(): float
    {
        return 3.0;
    }

    public function getCurrentBtcPriceGbp(): float
    {
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
