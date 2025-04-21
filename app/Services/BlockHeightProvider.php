<?php
declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Contracts\Cache\Repository as Cache;

final class BlockHeightProvider
{
    private const CACHE_KEY = 'max_possible_block_height';
    private const CACHE_TTL_MINUTES = 60;
    private const GENESIS_DATETIME = '2009-01-03 19:15:05';

    public function __construct(
        private Cache $cache,
    ) {
    }

    public function getMaxPossibleBlockHeight(): int
    {
        return $this->cache->remember(self::CACHE_KEY, now()->addMinutes(self::CACHE_TTL_MINUTES), function () {
            $genesisTimestamp = (new DateTimeImmutable(self::GENESIS_DATETIME, new DateTimeZone('UTC')))->getTimestamp();
            $currentTimestamp = now()->setTimezone('UTC')->getTimestamp();

            $elapsedSeconds = $currentTimestamp - $genesisTimestamp;
            $estimatedHeight = (int) floor($elapsedSeconds / 600);
            $buffer = (int) ceil($estimatedHeight * 0.06);

            return $estimatedHeight + $buffer;
        });
    }
}
