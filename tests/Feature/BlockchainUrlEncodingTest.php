<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * PromptInput::fromRaw() calls anything non-numeric a transaction id, and the
 * identifier was interpolated straight into the Blockstream URL. `?q=../../x`
 * therefore produced a request to /api/tx/../../x — user input steering the path
 * of an outbound call to a third party.
 */
final class BlockchainUrlEncodingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_traversal_attempt_is_rejected_before_any_request(): void
    {
        Http::fake(['*' => Http::response('{}', 200)]);

        $this->getJson('/api/prefetch?q=' . urlencode('../../../evil'))
            ->assertStatus(400);

        Http::assertNothingSent();
    }

    public function test_a_junk_identifier_is_rejected(): void
    {
        Http::fake(['*' => Http::response('{}', 200)]);

        $this->getJson('/api/prefetch?q=not-a-txid')->assertStatus(400);

        Http::assertNothingSent();
    }

    public function test_a_valid_txid_is_still_accepted(): void
    {
        Http::fake(['*' => Http::response('{}', 500)]);

        $txid = str_repeat('a', 64);

        $this->getJson('/api/prefetch?q=' . $txid)->assertStatus(404);

        Http::assertSent(static fn ($request): bool => str_contains($request->url(), $txid));
    }

    public function test_a_block_height_is_still_accepted(): void
    {
        Http::fake(['*' => Http::response('{}', 500)]);

        $this->getJson('/api/prefetch?q=210000')->assertStatus(404);

        Http::assertSent(static fn ($request): bool => str_contains($request->url(), '210000'));
    }
}
