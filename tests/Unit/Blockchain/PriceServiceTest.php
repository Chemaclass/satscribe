<?php

declare(strict_types=1);

namespace Tests\Unit\Blockchain;

use Carbon\Carbon;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\Client\Response;
use Modules\Blockchain\Application\Coingecko\PriceService;
use Modules\Shared\Domain\HttpClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class PriceServiceTest extends TestCase
{
    public function test_returns_zero_prices_when_disabled(): void
    {
        $http = self::createStub(HttpClientInterface::class);
        $cache = self::createStub(Repository::class);
        $logger = self::createStub(LoggerInterface::class);

        $service = new PriceService($http, $logger, $cache, enabled: false, now: now());

        $this->assertSame(0.0, $service->getCurrentBtcPriceUsd());
        $this->assertSame(0.0, $service->getCurrentBtcPriceEur());
        $this->assertSame(0.0, $service->getCurrentBtcPriceCny());
        $this->assertSame(0.0, $service->getCurrentBtcPriceGbp());
        $this->assertSame(0.0, $service->getBtcPriceUsdAt(0));
        $this->assertSame(0.0, $service->getBtcPriceEurAt(0));
    }

    public function test_historical_price_uses_cache(): void
    {
        $http = self::createStub(HttpClientInterface::class);
        $logger = self::createStub(LoggerInterface::class);
        $now = Carbon::parse('2025-01-15');
        $timestamp = Carbon::parse('2025-01-10')->timestamp;

        $cache = $this->createMock(Repository::class);
        $cache->expects($this->once())
            ->method('remember')
            ->with(
                'btc_historical_price:usd:10-01-2025',
                $this->anything(),
                $this->anything(),
            )
            ->willReturn(50000.0);

        $service = new PriceService($http, $logger, $cache, enabled: true, now: $now);

        $result = $service->getBtcPriceUsdAt($timestamp);

        $this->assertSame(50000.0, $result);
    }

    public function test_historical_price_returns_zero_for_old_timestamps(): void
    {
        $http = self::createStub(HttpClientInterface::class);
        $cache = self::createStub(Repository::class);
        $logger = self::createStub(LoggerInterface::class);
        $now = Carbon::parse('2025-01-15');
        $timestamp = Carbon::parse('2023-01-01')->timestamp; // More than 1 year ago

        $service = new PriceService($http, $logger, $cache, enabled: true, now: $now);

        $result = $service->getBtcPriceUsdAt($timestamp);

        $this->assertSame(0.0, $result);
    }

    public function test_current_price_uses_cache(): void
    {
        $http = self::createStub(HttpClientInterface::class);
        $logger = self::createStub(LoggerInterface::class);
        $now = Carbon::now();

        $cache = $this->createMock(Repository::class);
        $cache->expects($this->once())
            ->method('remember')
            ->with(
                'btc_prices',
                $this->anything(),
                $this->anything(),
            )
            ->willReturn(['usd' => 60000.0, 'eur' => 55000.0, 'cny' => 400000.0, 'gbp' => 48000.0]);

        $service = new PriceService($http, $logger, $cache, enabled: true, now: $now);

        $result = $service->getCurrentBtcPriceUsd();

        $this->assertSame(60000.0, $result);
    }

    public function test_historical_price_fetches_from_api_when_not_cached(): void
    {
        $now = Carbon::parse('2025-01-15');
        $timestamp = Carbon::parse('2025-01-10')->timestamp;

        $response = $this->createMock(Response::class);
        $response->method('successful')->willReturn(true);
        $response->method('json')->with('market_data.current_price.usd')->willReturn(50000.0);

        $http = $this->createMock(HttpClientInterface::class);
        $http->expects($this->once())
            ->method('get')
            ->willReturn($response);

        $logger = self::createStub(LoggerInterface::class);

        $cache = $this->createMock(Repository::class);
        $cache->method('remember')
            ->willReturnCallback(static fn ($key, $ttl, $callback) => $callback());

        $service = new PriceService($http, $logger, $cache, enabled: true, now: $now);

        $result = $service->getBtcPriceUsdAt($timestamp);

        $this->assertSame(50000.0, $result);
    }
}
