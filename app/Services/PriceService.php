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
    private const CACHE_KEY = 'btc_price_usd';
    private const CACHE_TTL_MINUTES = 15;

    public function __construct(
        private HttpClient $http,
        private LoggerInterface $logger,
        private Cache $cache,
    ) {
    }

    public function getCurrentBtcPriceUsd(): float
    {
        return $this->cache->remember(self::CACHE_KEY, now()->addMinutes(self::CACHE_TTL_MINUTES), function () {
            $response = $this->http->get(self::BASE_URL.'/v3/simple/price', [
                'ids' => 'bitcoin',
                'vs_currencies' => 'usd',
            ]);

            if (!$response->successful()) {
                $this->logger->warning('Failed to fetch Bitcoin price from Coingecko', [
                    'response' => $response->body(),
                ]);
                throw new RuntimeException('Failed to fetch BTC price');
            }

            return (float) $response->json('bitcoin.usd');
        });
    }
}
