<?php

declare(strict_types=1);

namespace Modules\Chat\Domain\Exception;

use RuntimeException;

final class MessageNotFound extends RuntimeException
{
    public static function withId(int $messageId): self
    {
        return new self("No message with id {$messageId}.");
    }
}
