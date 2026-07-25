<?php

declare(strict_types=1);

namespace Tests\Feature;

use Modules\Shared\Domain\Data\Blockchain\TransactionData;
use Modules\UtxoTrace\Domain\UtxoTraceFacadeInterface;
use Modules\UtxoTrace\Infrastructure\Http\Controller\TraceUtxoController;
use Tests\TestCase;

/**
 * Each level walks every input of every transaction found at the level above, so
 * the Blockstream requests one call makes grow exponentially with depth. The
 * route is unauthenticated and unthrottled and runs with a 300s limit, so an
 * unbounded `depth` let one request pin a worker and hammer the upstream API.
 */
final class TraceUtxoDepthTest extends TestCase
{
    private const TXID = 'f4184fc596403b9d638783cf57adfe4c75c605f6356fbc91338530e9831e9e16';

    public function test_an_excessive_depth_is_clamped(): void
    {
        $facade = $this->fakeFacade();

        $this->getJson('/api/trace-utxo/' . self::TXID . '?depth=500')->assertStatus(200);

        self::assertSame(TraceUtxoController::MAX_DEPTH, $facade->lastDepth);
    }

    public function test_a_negative_depth_becomes_one(): void
    {
        $facade = $this->fakeFacade();

        $this->getJson('/api/trace-utxo/' . self::TXID . '?depth=-5')->assertStatus(200);

        self::assertSame(1, $facade->lastDepth);
    }

    public function test_a_depth_within_range_is_kept(): void
    {
        $facade = $this->fakeFacade();

        $this->getJson('/api/trace-utxo/' . self::TXID . '?depth=3')->assertStatus(200);

        self::assertSame(3, $facade->lastDepth);
    }

    public function test_the_default_depth_is_two(): void
    {
        $facade = $this->fakeFacade();

        $this->getJson('/api/trace-utxo/' . self::TXID)->assertStatus(200);

        self::assertSame(2, $facade->lastDepth);
    }

    public function test_an_invalid_txid_never_reaches_the_tracer(): void
    {
        $facade = $this->fakeFacade();

        $this->getJson('/api/trace-utxo/not-a-txid')->assertStatus(400);

        self::assertNull($facade->lastDepth);
    }

    private function fakeFacade(): RecordingTraceFacade
    {
        $facade = new RecordingTraceFacade();

        $this->app->instance(UtxoTraceFacadeInterface::class, $facade);

        return $facade;
    }
}

final class RecordingTraceFacade implements UtxoTraceFacadeInterface
{
    public ?int $lastDepth = null;

    public function getUtxoBacktrace(string $txid, int $depth = 1): array
    {
        $this->lastDepth = $depth;

        return ['utxos' => [], 'references' => []];
    }

    /**
     * @return list<TransactionData>
     */
    public function getTransactionBacktrace(string $txid, int $depth = 10): array
    {
        return [];
    }

    public function formatForPrompt(array $trace): string
    {
        return '';
    }
}
