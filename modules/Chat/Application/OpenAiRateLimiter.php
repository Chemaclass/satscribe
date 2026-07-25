<?php

declare(strict_types=1);

namespace Modules\Chat\Application;

use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Daily quota for OpenAI-billed requests, keyed by tracking id.
 *
 * Shared by every action that spends an OpenAI call, so the window, the key
 * and the message stay defined in exactly one place.
 */
final readonly class OpenAiRateLimiter
{
    private const RATE_LIMIT_SECONDS = 86400; // 24 hours

    public function __construct(
        private string $trackingId = '',
        private int $maxAttempts = 1000,
    ) {
    }

    public function enforce(): void
    {
        $key = "openai:{$this->trackingId}";

        if (RateLimiter::tooManyAttempts($key, $this->maxAttempts)) {
            throw new ThrottleRequestsException(
                "You have reached the daily OpenAI limit of {$this->maxAttempts} requests.",
            );
        }

        RateLimiter::hit($key, self::RATE_LIMIT_SECONDS);
    }
}
