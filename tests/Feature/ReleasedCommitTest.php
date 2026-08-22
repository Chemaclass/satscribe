<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The footer shows which commit is live. The old shell deploy wrote
 * LAST_COMMIT_HASH into the server's .env; Kamal sets KAMAL_VERSION instead, so
 * the value has to come from there or the badge silently disappears.
 */
final class ReleasedCommitTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_released_commit_falls_back_to_the_version_kamal_sets(): void
    {
        putenv('KAMAL_VERSION=856495c1d24a794a0926ad56603c4b6611b1b293');

        try {
            $config = require base_path('config/app.php');

            self::assertSame('856495c1d24a794a0926ad56603c4b6611b1b293', $config['last_commit']);
        } finally {
            putenv('KAMAL_VERSION');
        }
    }

    public function test_the_footer_shows_the_short_released_commit(): void
    {
        config(['app.last_commit' => '856495c1d24a794a0926ad56603c4b6611b1b293']);

        $response = $this->get('/nostr');

        $response->assertOk();
        $response->assertSee('856495c', escape: false);
        $response->assertDontSee('856495c1d24', escape: false);
    }

    public function test_the_footer_hides_the_commit_when_it_is_unknown(): void
    {
        config(['app.last_commit' => 'unknown']);

        $response = $this->get('/nostr');

        $response->assertOk();
        $response->assertDontSee('Released commit', escape: false);
    }
}
