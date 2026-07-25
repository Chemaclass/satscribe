<?php

declare(strict_types=1);

namespace Tests\Unit\UtxoTrace;

use App\Models\UtxoTrace;
use Illuminate\Http\Client\Response;
use Modules\Shared\Domain\HttpClientInterface;
use Modules\UtxoTrace\Application\Tracer\UtxoTracer;
use Modules\UtxoTrace\Domain\Repository\UtxoTraceRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class UtxoTraceServiceTest extends TestCase
{
    public function test_returns_cached_result_when_available(): void
    {
        $expected = ['foo' => 'bar'];

        $repo = new class($expected) implements UtxoTraceRepositoryInterface {
            /**
             * @param  array<string, mixed>  $data
             */
            public function __construct(private readonly array $data)
            {
            }
            public function find(string $txid, int $depth): UtxoTrace
            {
                $trace = new UtxoTrace();
                $trace->result = $this->data;
                return $trace;
            }
            public function store(string $txid, int $depth, array $result): UtxoTrace
            {
                throw new RuntimeException('Should not be called');
            }
        };

        $http = self::createStub(HttpClientInterface::class);
        $logger = self::createStub(LoggerInterface::class);

        $service = new UtxoTracer($http, $logger, $repo);

        $this->assertSame($expected, $service->getBacktrace('tx', 1));
    }

    public function test_returns_empty_array_when_missing_vout(): void
    {
        $repo = new class() implements UtxoTraceRepositoryInterface {
            public function find(string $txid, int $depth): ?UtxoTrace
            {
                return null;
            }
            public function store(string $txid, int $depth, array $result): UtxoTrace
            {
                return new UtxoTrace();
            }
        };

        $response = $this->createConfiguredMock(Response::class, [
            'failed' => false,
            'json' => ['vin' => []],
        ]);

        $http = $this->createMock(HttpClientInterface::class);
        $http->method('get')->willReturn($response);

        $logger = $this->createStub(LoggerInterface::class);

        $service = new UtxoTracer($http, $logger, $repo);

        $this->assertSame([], $service->buildBacktrace('tx', 1));
    }

    /**
     * Esplora's `vout` objects carry no index field: the fixture below is the
     * real `/tx/{txid}` shape (verified against blockstream.info), and an
     * output is identified by its position. Reading a non-existent `n` made
     * every traced UTXO report `vout: null`.
     */
    public function test_vout_index_comes_from_the_output_position(): void
    {
        $tx = [
            'txid' => 'txvoutindex',
            'vin' => [],
            'vout' => [
                [
                    'scriptpubkey' => '0014a1b2',
                    'scriptpubkey_address' => 'bc1qfirst',
                    'scriptpubkey_asm' => 'OP_0 OP_PUSHBYTES_20 a1b2',
                    'scriptpubkey_type' => 'v0_p2wpkh',
                    'value' => 1000,
                ],
                [
                    'scriptpubkey' => '0014c3d4',
                    'scriptpubkey_address' => 'bc1qsecond',
                    'scriptpubkey_asm' => 'OP_0 OP_PUSHBYTES_20 c3d4',
                    'scriptpubkey_type' => 'v0_p2wpkh',
                    'value' => 2000,
                ],
                [
                    'scriptpubkey' => '6a0568656c6c6f',
                    'scriptpubkey_asm' => 'OP_RETURN OP_PUSHBYTES_5 68656c6c6f',
                    'scriptpubkey_type' => 'op_return',
                    'value' => 0,
                ],
            ],
        ];

        $service = new UtxoTracer(
            $this->httpReturning($tx),
            $this->createStub(LoggerInterface::class),
            $this->nullRepository(),
        );

        $result = $service->buildBacktrace('txvoutindex', 1);

        self::assertCount(3, $result);
        self::assertSame([0, 1, 2], array_column(array_column($result, 'utxo'), 'vout'));
        self::assertSame('bc1qfirst', $result[0]['utxo']['scriptpubkey_address']);
        self::assertSame('bc1qsecond', $result[1]['utxo']['scriptpubkey_address']);
        self::assertNull($result[2]['utxo']['scriptpubkey_address']);
        self::assertSame([1000, 2000, 0], array_column(array_column($result, 'utxo'), 'value'));
    }

    /**
     * The vout number survives the reference-deduplication pass, which keys
     * nodes on `txid|vout|value`.
     */
    public function test_referenced_trace_keeps_the_vout_index(): void
    {
        $tx = [
            'txid' => 'txrefindex',
            'vin' => [],
            'vout' => [
                ['scriptpubkey_address' => 'bc1qfirst', 'scriptpubkey_type' => 'v0_p2wpkh', 'value' => 1000],
                ['scriptpubkey_address' => 'bc1qsecond', 'scriptpubkey_type' => 'v0_p2wpkh', 'value' => 2000],
            ],
        ];

        $service = new UtxoTracer(
            $this->httpReturning($tx),
            $this->createStub(LoggerInterface::class),
            $this->nullRepository(),
        );

        $result = $service->getBacktrace('txrefindex', 1);

        self::assertSame([0, 1], array_column(array_column($result['utxos'], 'utxo'), 'vout'));
    }

    public function test_uses_transaction_cache_to_avoid_duplicate_requests(): void
    {
        $repo = new class() implements UtxoTraceRepositoryInterface {
            public function find(string $txid, int $depth): ?UtxoTrace
            {
                return null;
            }
            public function store(string $txid, int $depth, array $result): UtxoTrace
            {
                return new UtxoTrace();
            }
        };

        $tx0 = [
            'vin' => [
                ['txid' => 'tx1', 'vout' => 0],
                ['txid' => 'tx1', 'vout' => 1],
            ],
            'vout' => [
                ['scriptpubkey_type' => 'p2pkh', 'value' => 50],
            ],
        ];

        $tx1 = [
            'vin' => [],
            'vout' => [
                ['scriptpubkey_type' => 'p2pkh', 'value' => 25],
                ['scriptpubkey_type' => 'p2pkh', 'value' => 25],
            ],
        ];

        $respTx0 = $this->createConfiguredMock(Response::class, [
            'failed' => false,
            'json' => $tx0,
        ]);

        $respTx1 = $this->createConfiguredMock(Response::class, [
            'failed' => false,
            'json' => $tx1,
        ]);

        $http = $this->createMock(HttpClientInterface::class);
        $http->expects($this->exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls(
                ['https://blockstream.info/api/tx/tx0'],
                ['https://blockstream.info/api/tx/tx1'],
            )
            ->willReturnOnConsecutiveCalls($respTx0, $respTx1);

        $logger = $this->createStub(LoggerInterface::class);

        $service = new UtxoTracer($http, $logger, $repo);

        $service->buildBacktrace('tx0', 1);
    }

    /**
     * @param array<string, mixed> $tx
     */
    private function httpReturning(array $tx): HttpClientInterface
    {
        $response = $this->createConfiguredMock(Response::class, [
            'failed' => false,
            'json' => $tx,
        ]);

        $http = $this->createMock(HttpClientInterface::class);
        $http->method('get')->willReturn($response);

        return $http;
    }

    private function nullRepository(): UtxoTraceRepositoryInterface
    {
        return new class() implements UtxoTraceRepositoryInterface {
            public function find(string $txid, int $depth): ?UtxoTrace
            {
                return null;
            }

            public function store(string $txid, int $depth, array $result): UtxoTrace
            {
                return new UtxoTrace();
            }
        };
    }
}
