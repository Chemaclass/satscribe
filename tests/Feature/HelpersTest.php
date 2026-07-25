<?php

declare(strict_types=1);

namespace Tests\Feature;

use RuntimeException;
use Tests\TestCase;

final class HelpersTest extends TestCase
{
    public function test_nostr_pubkey_returns_the_stored_string(): void
    {
        session(['nostr_pubkey' => 'npub123']);

        self::assertSame('npub123', nostr_pubkey());
    }

    public function test_nostr_pubkey_is_null_when_unset(): void
    {
        self::assertNull(nostr_pubkey());
    }

    /**
     * A non-string here used to flow into tracking_id() and the chat ownership
     * comparison, where it would never match but also never announce itself.
     */
    public function test_nostr_pubkey_rejects_a_non_string_session_value(): void
    {
        session(['nostr_pubkey' => ['not', 'a', 'string']]);

        self::assertNull(nostr_pubkey());
    }

    public function test_config_int_reads_a_numeric_value(): void
    {
        config(['services.rate_limit.guest.max_attempts' => 7]);

        self::assertSame(7, config_int('services.rate_limit.guest.max_attempts'));
    }

    public function test_config_int_throws_on_a_missing_key(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Config [services.rate_limit.nope] must be numeric, got null.');

        config_int('services.rate_limit.nope');
    }
}
