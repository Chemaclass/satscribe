<?php

declare(strict_types=1);

namespace Modules\Blockchain\Application\Coingecko;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Client\ConnectionException;
use Modules\Blockchain\Domain\PriceServiceInterface;
use Modules\Shared\Domain\HttpClientInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

use function in_array;
use function is_array;

final readonly class PriceService implements PriceServiceInterface
{
    private const BASE_URL = 'https://api.coingecko.com/api';
    private const CACHE_KEY = 'btc_prices';
    private const CACHE_TTL_MINUTES = 15;
    private const FAILURE_CACHE_TTL_MINUTES = 1;
    private const HISTORICAL_CACHE_KEY_PREFIX = 'btc_historical_price';
    private const HISTORICAL_CACHE_TTL_DAYS = 30;
    private const SUPPORTED_CURRENCIES = ['usd', 'eur', 'cny', 'gbp'];

    public function __construct(
        private HttpClientInterface $http,
        private LoggerInterface $logger,
        private Cache $cache,
        private bool $enabled,
        private CarbonInterface $now,
    ) {
    }

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

    /**
     * @return array<string, float> currency code => BTC price
     */
    private function getPrices(): array
    {
        if (!$this->enabled) {
            return $this->defaultPrices();
        }

        $cached = $this->cache->get(self::CACHE_KEY);

        if (is_array($cached)) {
            return $this->toPrices($cached);
        }

        try {
            $prices = $this->fetchPrices();
        } catch (ConnectionException|RuntimeException $e) {
            $this->logger->warning('Failed to fetch Bitcoin price from Coingecko', [
                'error' => $e->getMessage(),
            ]);

            // Zeros are cached too, on a shorter TTL: without this an outage
            // costs every single request a full connection timeout, and the
            // ticker is decoration nobody should wait 15s for.
            $this->cache->put(
                self::CACHE_KEY,
                $this->defaultPrices(),
                $this->now->copy()->addMinutes(self::FAILURE_CACHE_TTL_MINUTES),
            );

            return $this->defaultPrices();
        }

        $this->cache->put(
            self::CACHE_KEY,
            $prices,
            $this->now->copy()->addMinutes(self::CACHE_TTL_MINUTES),
        );

        return $prices;
    }

    /**
     * @return array<string, float>
     */
    private function fetchPrices(): array
    {
        $response = $this->http->get(self::BASE_URL . '/v3/simple/price', [
            'ids' => 'bitcoin',
            'vs_currencies' => implode(',', self::SUPPORTED_CURRENCIES),
        ]);

        if (!$response->successful()) {
            throw new RuntimeException('Coingecko answered ' . $response->status());
        }

        return $this->toPrices($response->json('bitcoin'));
    }

    /**
     * Coingecko occasionally answers 200 with a partial or reshaped body, and
     * a cache entry outlives the shape that wrote it. Either way an absent or
     * non-numeric rate becomes 0.0 rather than being cast out of whatever
     * arrived.
     *
     * @return array<string, float>
     */
    private function toPrices(mixed $data): array
    {
        $prices = [];
        foreach (self::SUPPORTED_CURRENCIES as $currency) {
            $rate = is_array($data) ? ($data[$currency] ?? null) : null;
            $prices[$currency] = is_numeric($rate) ? (float) $rate : 0.0;
        }

        return $prices;
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
        $cacheKey = self::HISTORICAL_CACHE_KEY_PREFIX . ":{$currency}:{$date}";

        try {
            return $this->cache->remember(
                $cacheKey,
                $this->now->copy()->addDays(self::HISTORICAL_CACHE_TTL_DAYS),
                function () use ($currency, $date): float {
                    $response = $this->http->get(self::BASE_URL . '/v3/coins/bitcoin/history', [
                        'date' => $date,
                        'localization' => 'false',
                    ]);

                    if (!$response->successful()) {
                        throw new RuntimeException('Coingecko answered ' . $response->status());
                    }

                    $rate = $response->json("market_data.current_price.{$currency}");

                    return is_numeric($rate) ? (float) $rate : 0.0;
                },
            );
        } catch (ConnectionException|RuntimeException $e) {
            $this->logger->warning('Failed to fetch historical BTC price from Coingecko', [
                'currency' => $currency,
                'date' => $date,
                'error' => $e->getMessage(),
            ]);

            return 0.0;
        }
    }

    /**
     * @return array<string, float>
     */
    private function defaultPrices(): array
    {
        return array_fill_keys(self::SUPPORTED_CURRENCIES, 0.0);
    }
}
