<?php

declare(strict_types=1);

namespace Modules\Blockchain\Application\Coingecko;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Cache\Repository as Cache;
use Modules\Blockchain\Domain\PriceServiceInterface;
use Modules\Shared\Domain\HttpClientInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

use function in_array;

final readonly class PriceService implements PriceServiceInterface
{
    private const BASE_URL = 'https://api.coingecko.com/api';
    private const CACHE_KEY = 'btc_prices';
    private const CACHE_TTL_MINUTES = 15;
    private const SUPPORTED_CURRENCIES = ['usd', 'eur', 'cny', 'gbp'];

    public function __construct(
        private HttpClientInterface $http,
        private LoggerInterface $logger,
        private Cache $cache,
        private bool $enabled,
        private CarbonInterface $now,
    ) {
    }

    // --- Current price accessors ---

    public function getCurrentBtcPriceUsd(): float
    {
        return $this->getCurrentPrice('usd');
    }

    public function getCurrentBtcPriceEur(): float
    {
        return $this->getCurrentPrice('eur');
    }

    public function getCurrentBtcPriceCny(): float
    {
        return $this->getCurrentPrice('cny');
    }

    public function getCurrentBtcPriceGbp(): float
    {
        return $this->getCurrentPrice('gbp');
    }

    // --- Historical price accessors ---

    public function getBtcPriceUsdAt(int $timestamp): float
    {
        return $this->getHistoricalPrice('usd', $timestamp);
    }

    public function getBtcPriceEurAt(int $timestamp): float
    {
        return $this->getHistoricalPrice('eur', $timestamp);
    }

    private function getCurrentPrice(string $currency): float
    {
        return $this->getPrices()[$currency] ?? 0.0;
    }

    private function getPrices(): array
    {
        if (!$this->enabled) {
            return $this->defaultPrices();
        }

        return $this->cache->remember(self::CACHE_KEY, $this->now->addMinutes(self::CACHE_TTL_MINUTES), function (): array {
            $response = $this->http->get(self::BASE_URL . '/v3/simple/price', [
                'ids' => 'bitcoin',
                'vs_currencies' => implode(',', self::SUPPORTED_CURRENCIES),
            ]);

            if (!$response->successful()) {
                $this->logger->warning('Failed to fetch Bitcoin price from Coingecko', [
                    'response' => $response->body(),
                ]);

                throw new RuntimeException('Failed to fetch current BTC price');
            }

            $data = $response->json('bitcoin') ?? [];

            return array_map(
                static fn (string $currency) => (float) ($data[$currency] ?? 0.0),
                self::SUPPORTED_CURRENCIES,
            );
        });
    }

    private function getHistoricalPrice(string $currency, int $timestamp): float
    {
        if (!$this->enabled || !in_array($currency, self::SUPPORTED_CURRENCIES, true)) {
            return 0.0;
        }

        if (Carbon::createFromTimestamp($timestamp)->lt($this->now->copy()->subYear())) {
            return 0.0; // Out of free historical range
        }

        $date = date('d-m-Y', $timestamp);

        $response = $this->http->get(self::BASE_URL . '/v3/coins/bitcoin/history', [
            'date' => $date,
            'localization' => 'false',
        ]);

        if (!$response->successful()) {
            $this->logger->warning('Failed to fetch historical BTC price from Coingecko', [
                'currency' => $currency,
                'date' => $date,
                'response' => $response->body(),
            ]);

            throw new RuntimeException('Failed to fetch historical BTC price');
        }

        return (float) $response->json("market_data.current_price.{$currency}");
    }

    private function defaultPrices(): array
    {
        return array_fill_keys(self::SUPPORTED_CURRENCIES, 0.0);
    }
}
