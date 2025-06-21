<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\BlockstreamException;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Carbon;
use Psr\Log\LoggerInterface;
use RuntimeException;

final readonly class BlockHeightProvider
{
    private const URL = 'https://blockstream.info/api/blocks/tip/height';
    private const CACHE_KEY = 'max_possible_block_height';
    private const CACHE_TTL_MINUTES = 10;
    private const FALLBACK_HEIGHT = 100_000_000;
    private const BUFFER_HEIGHT = 1;

    public function __construct(
        private Cache $cache,
        private HttpClientInterface $http,
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

        $response = $this->http->get(self::URL);
        if ($response->failed()) {
            throw BlockstreamException::requestFailed($response->status());
        }

        $height = (int) $response->body();
        if ($height <= 0) {
            throw BlockstreamException::invalidBlockHeight($response->body());
        }

        $this->cache->put(
            self::CACHE_KEY,
            $height + self::BUFFER_HEIGHT,
            Carbon::now()->addMinutes(self::CACHE_TTL_MINUTES)
        );

        return $height;
    }
}
