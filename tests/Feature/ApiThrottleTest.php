<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\UtxoTrace\Domain\UtxoTraceFacadeInterface;
use Tests\TestCase;

/**
 * Laravel 11 dropped the default API throttle and it was never opted back in, so
 * every /api/* route was unmetered — including the UTXO trace, which fans out to
 * Blockstream on each call.
 *
 * The limit has to clear the paywall modal, which polls invoice status every
 * 1.5s (~40 requests a minute) for up to 4.5 minutes. Throttling that would
 * break payment confirmation, so these pin both ends: the honest rate passes and
 * a flood does not.
 */
final class ApiThrottleTest extends TestCase
{
    use RefreshDatabase;

    private const LIMIT = 120;
    private const TXID = 'f4184fc596403b9d638783cf57adfe4c75c605f6356fbc91338530e9831e9e16';

    public function test_the_paywall_polling_rate_is_not_throttled(): void
    {
        $this->fakeTracer();

        // A full minute of polling at the modal's cadence.
        for ($i = 0; $i < 40; ++$i) {
            $this->getJson('/api/trace-utxo/' . self::TXID)->assertStatus(200);
        }
    }

    public function test_a_flood_is_throttled(): void
    {
        $this->fakeTracer();

        for ($i = 0; $i < self::LIMIT; ++$i) {
            $this->getJson('/api/trace-utxo/' . self::TXID)->assertStatus(200);
        }

        $this->getJson('/api/trace-utxo/' . self::TXID)->assertStatus(429);
    }

    private function fakeTracer(): void
    {
        $this->app->instance(UtxoTraceFacadeInterface::class, new RecordingTraceFacade());
    }
}
