<?php

declare(strict_types=1);

namespace Modules\Blockchain\Application\Blockstream;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Carbon;
use Modules\Blockchain\Domain\Exception\BlockstreamException;
use Modules\Shared\Domain\HttpClientInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

final readonly class BlockHeightProvider
{
    private const URL = 'https://blockstream.info/api/blocks/tip/height';
    private const CACHE_KEY = 'max_possible_block_height';
    private const CURRENT_HEIGHT_CACHE_KEY = 'current_block_height';
    private const CACHE_TTL_MINUTES = 10;
    private const FALLBACK_HEIGHT = 100_000_000;
    private const BUFFER_HEIGHT = 1;

    public function __construct(
        private Cache $cache,
        private HttpClientInterface $http,
        private LoggerInterface $logger,
        private bool $enabled = false,
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
                    $this->logger->warning('[BlockHeightProvider] ' . $e->getMessage());
                    return self::FALLBACK_HEIGHT;
                }
            },
        );
    }

    /**
     * Cached like the maximum above. This value is rendered on every home and
     * chat page, and only the maximum ever read the cache, so each page view
     * made its own call to Blockstream — latency and rate-limit exposure on the
     * hottest path in the app. A failure is left uncached so the next request
     * retries rather than serving the error for the whole TTL.
     */
    public function getCurrentBlockHeight(): int
    {
        if (!$this->enabled) {
            return self::FALLBACK_HEIGHT;
        }

        return $this->cache->remember(
            self::CURRENT_HEIGHT_CACHE_KEY,
            Carbon::now()->addMinutes(self::CACHE_TTL_MINUTES),
            function (): int {
                $response = $this->http->get(self::URL);
                if ($response->failed()) {
                    throw BlockstreamException::requestFailed($response->status());
                }

                $height = (int) $response->body();
                if ($height <= 0) {
                    throw BlockstreamException::invalidBlockHeight($response->body());
                }

                return $height;
            },
        );
    }
}
