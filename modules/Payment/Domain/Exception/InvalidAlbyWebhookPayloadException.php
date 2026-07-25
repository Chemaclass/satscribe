<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Exception;

use RuntimeException;

use function sprintf;

final class InvalidAlbyWebhookPayloadException extends RuntimeException
{
    public static function missing(string $name): self
    {
        return new self('Missing ' . $name . ' in payload');
    }

    public static function malformed(string $name, string $expected): self
    {
        return new self(sprintf('Field %s in payload must be %s', $name, $expected));
    }
}
