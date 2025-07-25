<?php

declare(strict_types=1);

namespace Tests\Unit\Blockchain;

use Illuminate\Contracts\Cache\Repository;
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
}
