<?php
declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;

final class BlockHeightProvider
{
    public function getMaxPossibleBlockHeight(): int
    {
        $genesisTimestamp = (new DateTimeImmutable('2009-01-03 19:15:05', new DateTimeZone('UTC')))->getTimestamp();
        $currentTimestamp = now()->setTimezone('UTC')->getTimestamp();

        $elapsedSeconds = $currentTimestamp - $genesisTimestamp;
        $estimatedHeight = (int) floor($elapsedSeconds / 600);
        $buffer = (int) ceil($estimatedHeight * 0.06);

        return $estimatedHeight + $buffer;
    }
}
