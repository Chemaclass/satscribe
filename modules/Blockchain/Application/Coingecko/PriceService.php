<?php

declare(strict_types=1);

namespace Modules\Blockchain\Application\Coingecko;

use Illuminate\Contracts\Cache\Repository as Cache;
use Modules\Blockchain\Domain\PriceServiceInterface;
use Modules\Shared\Domain\HttpClientInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

final readonly class PriceService implements PriceServiceInterface
{
    private const BASE_URL = 'https://api.coingecko.com/api';
    private const CACHE_KEY = 'btc_prices';
    private const CACHE_TTL_MINUTES = 15;

    public function __construct(
        private HttpClientInterface $http,
        private LoggerInterface $logger,
        private Cache $cache,
        private bool $enabled,
    ) {
    }

    public function getCurrentBtcPriceUsd(): float
    {
        return $this->getPrices()['usd'];
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

    public function getBtcPriceUsdAt(int $timestamp): float
    {
        if (!$this->enabled) {
            return 0.0;
        }

        $date = date('d-m-Y', $timestamp);
        $response = $this->http->get(self::BASE_URL . '/v3/coins/bitcoin/history', [
            'date' => $date,
            'localization' => 'false',
        ]);

        if (!$response->successful()) {
            $this->logger->warning('Failed to fetch historical BTC price from Coingecko', [
                'date' => $date,
                'response' => $response->body(),
            ]);

            throw new RuntimeException('Failed to fetch BTC price');
        }

        return (float) $response->json('market_data.current_price.usd');
    }

    public function getBtcPriceEurAt(int $timestamp): float
    {
        if (!$this->enabled) {
            return 0.0;
        }

        $date = date('d-m-Y', $timestamp);
        $response = $this->http->get(self::BASE_URL . '/v3/coins/bitcoin/history', [
            'date' => $date,
            'localization' => 'false',
        ]);

        if (!$response->successful()) {
            $this->logger->warning('Failed to fetch historical BTC price from Coingecko', [
                'date' => $date,
                'response' => $response->body(),
            ]);

            throw new RuntimeException('Failed to fetch BTC price');
        }

        return (float) $response->json('market_data.current_price.eur');
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
            $response = $this->http->get(self::BASE_URL . '/v3/simple/price', [
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
}
