<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\UtxoTrace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\UtxoTrace\Domain\Repository\UtxoTraceRepositoryInterface;
use Modules\UtxoTrace\Infrastructure\Repository\UtxoTraceRepository;
use Tests\TestCase;

/**
 * The table is a cache with no expiry, so without a version a trace produced by
 * an older tracer is served for good. Rows written before the vout index was
 * fixed record `vout: null` for every output.
 */
final class UtxoTraceCacheVersionTest extends TestCase
{
    use RefreshDatabase;

    private const TXID = 'f4184fc596403b9d638783cf57adfe4c75c605f6356fbc91338530e9831e9e16';

    public function test_a_row_from_an_older_version_is_ignored(): void
    {
        UtxoTrace::create([
            'txid' => self::TXID,
            'depth' => 2,
            'version' => 0,
            'result' => ['utxos' => [], 'references' => []],
        ]);

        self::assertNull((new UtxoTraceRepository())->find(self::TXID, 2));
    }

    public function test_a_row_from_the_current_version_is_served(): void
    {
        UtxoTrace::create([
            'txid' => self::TXID,
            'depth' => 2,
            'version' => UtxoTraceRepositoryInterface::CURRENT_VERSION,
            'result' => ['utxos' => [], 'references' => []],
        ]);

        self::assertNotNull((new UtxoTraceRepository())->find(self::TXID, 2));
    }

    /**
     * (txid, depth) is unique, so recomputing has to replace the stale row
     * rather than fail or leave both behind.
     */
    public function test_storing_replaces_a_row_left_by_an_older_version(): void
    {
        UtxoTrace::create([
            'txid' => self::TXID,
            'depth' => 2,
            'version' => 0,
            'result' => ['utxos' => [], 'references' => ['stale' => true]],
        ]);

        $repository = new UtxoTraceRepository();
        $repository->store(self::TXID, 2, ['utxos' => [], 'references' => []]);

        self::assertSame(1, UtxoTrace::where('txid', self::TXID)->count());
        self::assertNotNull($repository->find(self::TXID, 2));
    }
}
