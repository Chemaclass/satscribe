<?php

declare(strict_types=1);

namespace Tests\Unit\Blockchain;

use Carbon\Carbon;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\Client\ConnectionException;
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
        $timestamp = (int) Carbon::parse('2025-01-10')->timestamp;

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
        $timestamp = (int) Carbon::parse('2023-01-01')->timestamp; // More than 1 year ago

        $service = new PriceService($http, $logger, $cache, enabled: true, now: $now);

        $result = $service->getBtcPriceUsdAt($timestamp);

        $this->assertSame(0.0, $result);
    }

    public function test_current_price_uses_cache(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->expects($this->never())->method('get');

        $logger = self::createStub(LoggerInterface::class);

        $cache = $this->createMock(Repository::class);
        $cache->expects($this->once())
            ->method('get')
            ->with('btc_prices')
            ->willReturn(['usd' => 60000.0, 'eur' => 55000.0, 'cny' => 400000.0, 'gbp' => 48000.0]);

        $service = new PriceService($http, $logger, $cache, enabled: true, now: Carbon::now());

        $result = $service->getCurrentBtcPriceUsd();

        $this->assertSame(60000.0, $result);
    }

    public function test_current_price_caches_a_successful_lookup(): void
    {
        $response = $this->createMock(Response::class);
        $response->method('successful')->willReturn(true);
        $response->method('json')->with('bitcoin')->willReturn([
            'usd' => 60000.5,
            'eur' => 55000.5,
            'cny' => 400000.5,
            'gbp' => 48000.5,
        ]);

        $http = $this->createMock(HttpClientInterface::class);
        $http->expects($this->once())->method('get')->willReturn($response);

        $logger = self::createStub(LoggerInterface::class);
        $now = Carbon::parse('2025-01-15 12:00:00');

        $cache = $this->createMock(Repository::class);
        $cache->method('get')->willReturn(null);
        $cache->expects($this->once())
            ->method('put')
            ->with(
                'btc_prices',
                ['usd' => 60000.5, 'eur' => 55000.5, 'cny' => 400000.5, 'gbp' => 48000.5],
                self::callback(static fn (Carbon $expiry): bool => $expiry->equalTo(Carbon::parse('2025-01-15 12:15:00'))),
            );

        $service = new PriceService($http, $logger, $cache, enabled: true, now: $now);

        $this->assertSame(60000.5, $service->getCurrentBtcPriceUsd());
    }

    public function test_historical_price_fetches_from_api_when_not_cached(): void
    {
        $now = Carbon::parse('2025-01-15');
        $timestamp = (int) Carbon::parse('2025-01-10')->timestamp;

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

    public function test_current_price_returns_zero_when_coingecko_is_unreachable(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->method('get')
            ->willThrowException(new ConnectionException('cURL error 28: Operation timed out after 15001 milliseconds'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning');

        $cache = $this->createMock(Repository::class);
        $cache->method('get')->willReturn(null);

        $service = new PriceService($http, $logger, $cache, enabled: true, now: Carbon::now());

        $this->assertSame(0.0, $service->getCurrentBtcPriceUsd());
    }

    public function test_an_unreachable_coingecko_is_cached_briefly_so_the_next_request_does_not_stall(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->method('get')
            ->willThrowException(new ConnectionException('cURL error 28: Operation timed out after 15001 milliseconds'));

        $logger = self::createStub(LoggerInterface::class);
        $now = Carbon::parse('2025-01-15 12:00:00');

        $cache = $this->createMock(Repository::class);
        $cache->method('get')->willReturn(null);
        $cache->expects($this->once())
            ->method('put')
            ->with(
                'btc_prices',
                ['usd' => 0.0, 'eur' => 0.0, 'cny' => 0.0, 'gbp' => 0.0],
                self::callback(static fn (Carbon $expiry): bool => $expiry->equalTo(Carbon::parse('2025-01-15 12:01:00'))),
            );

        $service = new PriceService($http, $logger, $cache, enabled: true, now: $now);

        $service->getCurrentBtcPriceUsd();
    }

    public function test_historical_price_returns_zero_when_coingecko_is_unreachable(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->method('get')
            ->willThrowException(new ConnectionException('cURL error 28: Operation timed out after 15001 milliseconds'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning');

        $now = Carbon::parse('2025-01-15');
        $timestamp = (int) Carbon::parse('2025-01-10')->timestamp;

        $cache = $this->createMock(Repository::class);
        $cache->method('remember')
            ->willReturnCallback(static fn ($key, $ttl, $callback) => $callback());

        $service = new PriceService($http, $logger, $cache, enabled: true, now: $now);

        $this->assertSame(0.0, $service->getBtcPriceUsdAt($timestamp));
    }
}
