<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Nostr\Domain\EventSignatureVerifierInterface;
use Tests\TestCase;

/**
 * Logging in with Nostr is the app's only authentication, and it had no
 * coverage.
 *
 * Session rotation on login/logout is deliberately not asserted here: the test
 * harness issues a fresh session id for every request regardless, so such an
 * assertion passes whether or not the code rotates anything.
 */
final class NostrAuthTest extends TestCase
{
    use RefreshDatabase;

    private const PUBKEY = 'a1b2c3d4e5f60718293a4b5c6d7e8f90a1b2c3d4e5f60718293a4b5c6d7e8f90';

    public function test_a_valid_login_stores_the_pubkey(): void
    {
        $this->fakeVerifier(valid: true);

        $challenge = $this->getJson(route('nostr.challenge'))->json('challenge');

        $response = $this->postJson(route('nostr.login'), ['event' => $this->event($challenge)]);

        $response->assertStatus(200);
        $response->assertJsonPath('pubkey', self::PUBKEY);
        self::assertSame(self::PUBKEY, session('nostr_pubkey'));
    }

    public function test_a_replayed_challenge_is_rejected(): void
    {
        $this->fakeVerifier(valid: true);

        $challenge = $this->getJson(route('nostr.challenge'))->json('challenge');
        $this->postJson(route('nostr.login'), ['event' => $this->event($challenge)])->assertStatus(200);

        $this->postJson(route('nostr.login'), ['event' => $this->event($challenge)])
            ->assertStatus(422)
            ->assertJsonPath('error', 'Invalid challenge');
    }

    public function test_a_bad_signature_is_rejected(): void
    {
        $this->fakeVerifier(valid: false);

        $challenge = $this->getJson(route('nostr.challenge'))->json('challenge');

        $this->postJson(route('nostr.login'), ['event' => $this->event($challenge)])
            ->assertStatus(422)
            ->assertJsonPath('error', 'Invalid signature');

        self::assertNull(session('nostr_pubkey'));
    }

    public function test_a_malformed_pubkey_is_rejected(): void
    {
        $this->fakeVerifier(valid: true);

        $challenge = $this->getJson(route('nostr.challenge'))->json('challenge');
        $event = ['pubkey' => 'not-a-pubkey', 'content' => $challenge];

        $this->postJson(route('nostr.login'), ['event' => $event])
            ->assertStatus(422)
            ->assertJsonPath('error', 'Invalid pubkey');
    }

    public function test_logout_clears_the_pubkey(): void
    {
        $this->fakeVerifier(valid: true);

        $challenge = $this->getJson(route('nostr.challenge'))->json('challenge');
        $this->postJson(route('nostr.login'), ['event' => $this->event($challenge)])->assertStatus(200);

        $this->postJson(route('nostr.logout'))->assertStatus(200);

        self::assertNull(session('nostr_pubkey'));
    }

    /**
     * @return array<string, mixed>
     */
    private function event(mixed $challenge): array
    {
        return [
            'id' => 'event-id',
            'pubkey' => self::PUBKEY,
            'content' => $challenge,
            'sig' => 'signature',
        ];
    }

    private function fakeVerifier(bool $valid): void
    {
        $verifier = new class($valid) implements EventSignatureVerifierInterface {
            public function __construct(private readonly bool $valid)
            {
            }

            public function verify(array $event): bool
            {
                return $this->valid;
            }
        };

        $this->app->instance(EventSignatureVerifierInterface::class, $verifier);
    }
}
