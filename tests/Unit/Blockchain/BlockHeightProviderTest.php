<?php

declare(strict_types=1);

namespace Tests\Unit\Blockchain;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Client\Response;
use Modules\Blockchain\Application\Blockstream\BlockHeightProvider;
use Modules\Blockchain\Domain\Exception\BlockstreamException;
use Modules\Shared\Domain\HttpClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class BlockHeightProviderTest extends TestCase
{
    public function test_get_current_block_height_returns_fallback_when_disabled(): void
    {
        $cache = self::createStub(Cache::class);
        $http = self::createStub(HttpClientInterface::class);
        $logger = self::createStub(LoggerInterface::class);

        $provider = new BlockHeightProvider($cache, $http, $logger, enabled: false);

        $this->assertSame(100_000_000, $provider->getCurrentBlockHeight());
    }

    public function test_get_current_block_height_returns_api_height_when_enabled(): void
    {
        $cache = $this->createMock(Cache::class);
        $cache->expects($this->once())
            ->method('put')
            ->with('max_possible_block_height', 800001, $this->anything());

        $response = $this->createMock(Response::class);
        $response->method('failed')->willReturn(false);
        $response->method('body')->willReturn('800000');

        $http = $this->createMock(HttpClientInterface::class);
        $http->expects($this->once())
            ->method('get')
            ->with('https://blockstream.info/api/blocks/tip/height')
            ->willReturn($response);

        $logger = self::createStub(LoggerInterface::class);

        $provider = new BlockHeightProvider($cache, $http, $logger, enabled: true);

        $this->assertSame(800000, $provider->getCurrentBlockHeight());
    }

    public function test_get_current_block_height_throws_on_api_failure(): void
    {
        $cache = self::createStub(Cache::class);

        $response = $this->createMock(Response::class);
        $response->method('failed')->willReturn(true);
        $response->method('status')->willReturn(500);

        $http = $this->createMock(HttpClientInterface::class);
        $http->method('get')->willReturn($response);

        $logger = self::createStub(LoggerInterface::class);

        $provider = new BlockHeightProvider($cache, $http, $logger, enabled: true);

        $this->expectException(BlockstreamException::class);
        $provider->getCurrentBlockHeight();
    }

    public function test_get_current_block_height_throws_on_invalid_height(): void
    {
        $cache = self::createStub(Cache::class);

        $response = $this->createMock(Response::class);
        $response->method('failed')->willReturn(false);
        $response->method('body')->willReturn('invalid');

        $http = $this->createMock(HttpClientInterface::class);
        $http->method('get')->willReturn($response);

        $logger = self::createStub(LoggerInterface::class);

        $provider = new BlockHeightProvider($cache, $http, $logger, enabled: true);

        $this->expectException(BlockstreamException::class);
        $provider->getCurrentBlockHeight();
    }

    public function test_get_max_possible_block_height_uses_cache(): void
    {
        $cache = $this->createMock(Cache::class);
        $cache->expects($this->once())
            ->method('remember')
            ->with('max_possible_block_height', $this->anything(), $this->anything())
            ->willReturn(800001);

        $http = self::createStub(HttpClientInterface::class);
        $logger = self::createStub(LoggerInterface::class);

        $provider = new BlockHeightProvider($cache, $http, $logger, enabled: true);

        $this->assertSame(800001, $provider->getMaxPossibleBlockHeight());
    }

    public function test_get_max_possible_block_height_returns_current_plus_buffer(): void
    {
        $http = self::createStub(HttpClientInterface::class);
        $logger = self::createStub(LoggerInterface::class);

        $cache = $this->createMock(Cache::class);
        $cache->method('remember')
            ->willReturnCallback(static fn ($key, $ttl, $callback) => $callback());

        // Provider is disabled, so getCurrentBlockHeight returns fallback
        // and then we add BUFFER_HEIGHT (1)
        $provider = new BlockHeightProvider($cache, $http, $logger, enabled: false);

        // When disabled, returns FALLBACK_HEIGHT (100_000_000) + BUFFER_HEIGHT (1) = 100_000_001
        $this->assertSame(100_000_001, $provider->getMaxPossibleBlockHeight());
    }
}
