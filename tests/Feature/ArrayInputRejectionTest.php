<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every one of these reads a request field that the client controls the type
 * of. Casting an array to string raises "Array to string conversion", which
 * Laravel escalates to an ErrorException — so `?q[]=x` turned an endpoint into
 * a 500 that any visitor could trigger.
 */
final class ArrayInputRejectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_prefetch_handles_an_array_query(): void
    {
        $response = $this->getJson('/api/prefetch?q[]=210000');

        self::assertNotSame(500, $response->getStatusCode());
    }

    public function test_faq_search_handles_an_array_search(): void
    {
        $response = $this->get('/faq?search[]=fees');

        self::assertNotSame(500, $response->getStatusCode());
    }

    public function test_nostr_login_handles_an_array_pubkey(): void
    {
        $response = $this->postJson('/auth/nostr/login', [
            'event' => ['pubkey' => ['deadbeef'], 'sig' => 'x', 'id' => 'x'],
        ]);

        self::assertNotSame(500, $response->getStatusCode());
    }
}
