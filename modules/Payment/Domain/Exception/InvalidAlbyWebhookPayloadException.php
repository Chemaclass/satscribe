<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Exception;

use RuntimeException;

final class InvalidAlbyWebhookPayloadException extends RuntimeException
{
    public static function missing(string $name): self
    {
        return new self('Missing ' . $name . ' in payload');
    }
}
