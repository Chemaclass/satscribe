<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\UtxoTrace;
use App\Repositories\UtxoTraceRepositoryInterface;
use App\Services\UtxoTraceService;
use Illuminate\Http\Client\Factory as HttpClient;
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

        $http = self::createStub(HttpClient::class);
        $logger = self::createStub(LoggerInterface::class);

        $service = new UtxoTraceService($http, $logger, $repo);

        $this->assertSame($expected, $service->traceWithReferences('tx', 1));
    }
}
