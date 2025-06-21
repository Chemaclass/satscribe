<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\UtxoTrace;
use App\Repositories\UtxoTraceRepositoryInterface;
use App\Services\HttpClientInterface;
use App\Services\UtxoTraceService;
use Illuminate\Http\Client\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class UtxoTraceServiceTest extends TestCase
{
    public function test_returns_cached_result_when_available(): void
    {
        $expected = ['foo' => 'bar'];

        $repo = new class($expected) implements UtxoTraceRepositoryInterface {
            public function __construct(private array $data) {}
            public function find(string $txid, int $depth): ?UtxoTrace
            {
                $trace = new UtxoTrace();
                $trace->result = $this->data;
                return $trace;
            }
            public function store(string $txid, int $depth, array $result): UtxoTrace
            {
                throw new \RuntimeException('Should not be called');
            }
        };

        $http = self::createStub(HttpClientInterface::class);
        $logger = self::createStub(LoggerInterface::class);

        $service = new UtxoTraceService($http, $logger, $repo);

        $this->assertSame($expected, $service->traceWithReferences('tx', 1));
    }

    public function test_returns_empty_array_when_missing_vout(): void
    {
        $repo = new class() implements UtxoTraceRepositoryInterface {
            public function find(string $txid, int $depth): ?UtxoTrace { return null; }
            public function store(string $txid, int $depth, array $result): UtxoTrace { return new UtxoTrace(); }
        };

        $response = $this->createConfiguredMock(Response::class, [
            'failed' => false,
            'json' => ['vin' => []],
        ]);

        $http = $this->createMock(HttpClientInterface::class);
        $http->method('get')->willReturn($response);

        $logger = $this->createStub(LoggerInterface::class);

        $service = new UtxoTraceService($http, $logger, $repo);

        $this->assertSame([], $service->trace('tx', 1));
    }

    public function test_uses_transaction_cache_to_avoid_duplicate_requests(): void
    {
        $repo = new class() implements UtxoTraceRepositoryInterface {
            public function find(string $txid, int $depth): ?UtxoTrace { return null; }
            public function store(string $txid, int $depth, array $result): UtxoTrace { return new UtxoTrace(); }
        };

        $tx0 = [
            'vin' => [
                ['txid' => 'tx1', 'vout' => 0],
                ['txid' => 'tx1', 'vout' => 1],
            ],
            'vout' => [
                ['n' => 0, 'value' => 50],
            ],
        ];

        $tx1 = [
            'vin' => [],
            'vout' => [
                ['n' => 0, 'value' => 25],
                ['n' => 1, 'value' => 25],
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
                ['https://blockstream.info/api/tx/tx1']
            )
            ->willReturnOnConsecutiveCalls($respTx0, $respTx1);

        $logger = $this->createStub(LoggerInterface::class);

        $service = new UtxoTraceService($http, $logger, $repo);

        $service->trace('tx0', 1);
    }
}
