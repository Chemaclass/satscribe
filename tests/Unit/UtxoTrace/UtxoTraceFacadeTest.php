<?php

declare(strict_types=1);

namespace Tests\Unit\UtxoTrace;

use App\Models\UtxoTrace;
use Modules\Blockchain\Domain\BlockchainFacadeInterface;
use Modules\Shared\Domain\Data\Blockchain\BlockchainData;
use Modules\Shared\Domain\Data\Blockchain\TransactionData;
use Modules\Shared\Domain\Data\Chat\PromptInput;
use Modules\Shared\Domain\HttpClientInterface;
use Modules\UtxoTrace\Application\Tracer\TransactionTracer;
use Modules\UtxoTrace\Application\Tracer\UtxoTracer;
use Modules\UtxoTrace\Application\UtxoTraceFacade;
use Modules\UtxoTrace\Domain\Repository\UtxoTraceRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class UtxoTraceFacadeTest extends TestCase
{
    public function test_get_utxo_backtrace_returns_cached_result(): void
    {
        $expected = ['foo' => 'bar'];

        $repo = new class($expected) implements UtxoTraceRepositoryInterface {
            public function __construct(private readonly array $data)
            {
            }
            public function find(string $txid, int $depth): ?UtxoTrace
            {
                $trace = new UtxoTrace();
                $trace->result = $this->data;
                return $trace;
            }
            public function store(string $txid, int $depth, array $result): UtxoTrace
            {
                throw new RuntimeException('should not store');
            }
        };

        $http = self::createStub(HttpClientInterface::class);
        $logger = self::createStub(LoggerInterface::class);
        $utxoTracer = new UtxoTracer($http, $logger, $repo);

        $txTracer = new TransactionTracer(
            self::createStub(BlockchainFacadeInterface::class),
            $logger,
        );

        $facade = new UtxoTraceFacade($utxoTracer, $txTracer);

        $this->assertSame($expected, $facade->getUtxoBacktrace('tx', 1));
    }

    public function test_get_transaction_backtrace_returns_trace(): void
    {
        $tx = new TransactionData(txid: 'tx1', vin: [['is_coinbase' => true]]);

        $facadeStub = new class($tx) implements BlockchainFacadeInterface {
            public function __construct(private readonly TransactionData $tx)
            {
            }
            public function getMaxPossibleBlockHeight(): int
            {
                return 0;
            }
            public function getCurrentBlockHeight(): int
            {
                return 0;
            }
            public function getBlockchainData(PromptInput $input): BlockchainData
            {
                return BlockchainData::forTransaction($this->tx);
            }
        };

        $logger = self::createStub(LoggerInterface::class);
        $txTracer = new TransactionTracer($facadeStub, $logger);

        $utxoTracer = new UtxoTracer(
            self::createStub(HttpClientInterface::class),
            $logger,
            new class() implements UtxoTraceRepositoryInterface {
                public function find(string $txid, int $depth): ?UtxoTrace
                {
                    return null;
                }
                public function store(string $txid, int $depth, array $result): UtxoTrace
                {
                    return new UtxoTrace();
                }
            },
        );

        $facade = new UtxoTraceFacade($utxoTracer, $txTracer);

        $this->assertSame([$tx], $facade->getTransactionBacktrace('tx1'));
    }

    public function test_format_for_prompt_delegates(): void
    {
        $logger = self::createStub(LoggerInterface::class);
        $txTracer = new TransactionTracer(
            self::createStub(BlockchainFacadeInterface::class),
            $logger,
        );
        $utxoTracer = new UtxoTracer(
            self::createStub(HttpClientInterface::class),
            $logger,
            new class() implements UtxoTraceRepositoryInterface {
                public function find(string $txid, int $depth): ?UtxoTrace
                {
                    return null;
                }
                public function store(string $txid, int $depth, array $result): UtxoTrace
                {
                    return new UtxoTrace();
                }
            },
        );

        $facade = new UtxoTraceFacade($utxoTracer, $txTracer);

        $tx = new TransactionData(txid: 'tx1', vin: [['is_coinbase' => true]]);
        $expected = "Transaction Backtrace\n1. tx1";

        $this->assertSame($expected, $facade->formatForPrompt([$tx]));
    }
}
