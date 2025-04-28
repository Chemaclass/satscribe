<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Support\Carbon;
use Psr\Log\LoggerInterface;
use RuntimeException;

final readonly class BlockHeightProvider
{
    private const CACHE_KEY = 'max_possible_block_height';
    private const CACHE_TTL_MINUTES = 10;
    private const FALLBACK_HEIGHT = 100_000_000;
    private const BUFFER_HEIGHT = 1;

    public function __construct(
        private Cache $cache,
        private HttpClient $http,
        private LoggerInterface $logger,
        private bool $enabled,
    ) {
    }

    public function getMaxPossibleBlockHeight(): int
    {
        return $this->cache->remember(
            self::CACHE_KEY,
            Carbon::now()->addMinutes(self::CACHE_TTL_MINUTES),
            function () {
                try {
                    return $this->getCurrentBlockHeight() + self::BUFFER_HEIGHT;
                } catch (RuntimeException $e) {
                    $this->logger->warning('[BlockHeightProvider] '.$e->getMessage());
                    return self::FALLBACK_HEIGHT;
                }
            }
        );
    }

    public function getCurrentBlockHeight(): int
    {
        if (!$this->enabled) {
            return self::FALLBACK_HEIGHT;
        }

        $url = 'https://blockstream.info/api/blocks/tip/height';
        $response = $this->http->get($url);
        if ($response->failed()) {
            throw new RuntimeException("Blockstream API request failed for [$url]. Status: {$response->status()}");
        }

        $height = (int) $response->body();
        if ($height <= 0) {
            throw new RuntimeException("Blockstream API returned invalid block height: {$response->body()}");
        }

        $this->cache->put(
            self::CACHE_KEY,
            $height + self::BUFFER_HEIGHT,
            Carbon::now()->addMinutes(self::CACHE_TTL_MINUTES)
        );

        return $height;
    }
}
