<?php

declare(strict_types=1);

namespace Modules\Blockchain\Domain\Exception;

use RuntimeException;

final class BlockstreamException extends RuntimeException
{
    public static function requestFailed(int $status): self
    {
        return new self("Blockstream API request failed. Status: {$status}");
    }

    public static function invalidBlockHeight(string $body): self
    {
        return new self("Blockstream API returned invalid block height: {$body}");
    }
}
