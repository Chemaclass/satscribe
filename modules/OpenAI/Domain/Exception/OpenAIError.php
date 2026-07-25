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
    /**
     * A 200 that carried no usable content. Worth its own factory because it is
     * the one model failure that produces no exception of its own — without it
     * the caller cannot tell success from silence.
     */
    public static function emptyResponse(): self
    {
        return new self('The model returned an empty response. Please try again or pick another model.');
    }
}
