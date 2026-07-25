<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Cache\RateLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The daily OpenAI quota is enforced from inside the streaming generator, so it
 * throws after the SSE headers have already been sent. streamEvents() caught
 * only BlockchainException and OpenAIError, so a throttled request tore down
 * the stream with no terminal event and the UI sat on a skeleton forever.
 */
final class StreamThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_exhausted_quota_emits_a_terminal_error_event(): void
    {
        $limiter = app(RateLimiter::class);
        $key = 'openai:' . tracking_id();
        $max = (int) config('services.openai.max_attempts');

        for ($i = 0; $i <= $max; ++$i) {
            $limiter->hit($key, 86400);
        }

        $response = $this->post('/stream', [
            'search' => '210000',
            'question' => 'What happened here?',
        ]);

        $response->assertStatus(200);

        $body = $response->streamedContent();

        self::assertStringContainsString('"type":"error"', $body);
        self::assertStringContainsString('daily OpenAI limit', $body);
    }
}
