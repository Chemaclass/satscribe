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

    /**
     * The provider's own status is the only thing that distinguishes a bad key
     * from an exhausted quota from a model the account cannot use. Naming the
     * cause is safe — it says nothing about the key itself — and without it the
     * operator has a failing site and a generic message.
     */
    public static function providerRejected(string $provider, int $status): self
    {
        $reason = match (true) {
            $status === 401, $status === 403 => 'rejected the configured API key',
            $status === 404 => 'does not offer the configured model to this account',
            $status === 429 => 'is rate limited or out of quota',
            $status >= 500 => 'is having an outage',
            default => "returned an unexpected status ({$status})",
        };

        return new self("{$provider} {$reason}. Try another model, or add your own API key.");
    }
}
