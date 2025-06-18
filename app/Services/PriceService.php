<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Client\Factory as HttpClient;
use Psr\Log\LoggerInterface;
use RuntimeException;

final readonly class PriceService
{
    private const BASE_URL = 'https://api.coingecko.com/api';
    private const CACHE_KEY = 'btc_prices';
    private const CACHE_TTL_MINUTES = 15;

    public function __construct(
        private HttpClient $http,
        private LoggerInterface $logger,
        private Cache $cache,
        private bool $enabled,
    ) {
    }

    public function getCurrentBtcPriceUsd(): float
    {
        return $this->getPrices()['usd'];
    }

    private function getPrices(): array
    {
        if (!$this->enabled) {
            return [
                'usd' => 0.0,
                'eur' => 0.0,
                'cny' => 0.0,
                'gbp' => 0.0,
            ];
        }

        return $this->cache->remember(self::CACHE_KEY, now()->addMinutes(self::CACHE_TTL_MINUTES), function () {
            $response = $this->http->get(self::BASE_URL.'/v3/simple/price', [
                'ids' => 'bitcoin',
                'vs_currencies' => 'usd,eur,cny,gbp',
            ]);

            if (!$response->successful()) {
                $this->logger->warning('Failed to fetch Bitcoin price from Coingecko', [
                    'response' => $response->body(),
                ]);
                throw new RuntimeException('Failed to fetch BTC price');
            }

            return [
                'usd' => (float) $response->json('bitcoin.usd'),
                'eur' => (float) $response->json('bitcoin.eur'),
                'cny' => (float) $response->json('bitcoin.cny'),
                'gbp' => (float) $response->json('bitcoin.gbp'),
            ];
        });
    }

    public function getCurrentBtcPriceEur(): float
    {
        return $this->getPrices()['eur'];
    }

    public function getCurrentBtcPriceCny(): float
    {
        return $this->getPrices()['cny'];
    }

    public function getCurrentBtcPriceGbp(): float
    {
        return $this->getPrices()['gbp'];
    }
}
