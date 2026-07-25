<?php

declare(strict_types=1);

namespace Modules\Blockchain\Domain\Exception;

use RuntimeException;

final class BlockchainException extends RuntimeException
{
    public static function blockOrTxFetchFailed(string $hash): self
    {
        return new self('Block or transactions fetch failed: ' . $hash);
    }

    public static function txLookupFailed(string $txid): self
    {
        return new self('Transaction lookup failed: ' . $txid);
    }

    /**
     * A 200 whose body is not the documented shape. Treated as a fetch failure
     * so callers keep the one error path they already handle.
     */
    public static function malformedPayload(string $reference, string $field): self
    {
        return new self("Malformed Blockstream payload for {$reference}: {$field}");
    }
}
