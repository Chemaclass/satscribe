<?php

declare(strict_types=1);

namespace Modules\OpenAI\Domain\Exception;

/**
 * A request asked for a provider/model that is not in the registry, or handed
 * over a malformed key. Never carries the key itself in its message.
 */
final class UnsupportedModelError extends OpenAIError
{
}
