<?php
declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class BlockchainException extends RuntimeException
{
    public static function blockOrTxFetchFailed(string $hash): self
    {
        return new self('Block or transactions fetch failed: '.$hash);
    }

    public static function txLookupFailed(string $txid): self
    {
        return new self('Transaction lookup failed: '.$txid);
    }
}
