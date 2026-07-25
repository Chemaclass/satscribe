<?php

declare(strict_types=1);

namespace Modules\OpenAI\Domain\Exception;

use RuntimeException;

/**
 * Not final: {@see UnsupportedModelError} extends it so callers that already
 * catch OpenAIError keep handling model-selection failures unchanged.
 */
class OpenAIError extends RuntimeException
{
}
