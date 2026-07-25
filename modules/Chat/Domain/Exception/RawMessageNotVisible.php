<?php

declare(strict_types=1);

namespace Modules\Chat\Domain\Exception;

use RuntimeException;

/**
 * Separate from MessageNotFound so the caller can answer 403 rather than 404:
 * the message exists, it just belongs to a chat this visitor may not see.
 */
final class RawMessageNotVisible extends RuntimeException
{
    public static function withId(int $messageId): self
    {
        return new self("Message {$messageId} belongs to a chat you cannot view.");
    }
}
