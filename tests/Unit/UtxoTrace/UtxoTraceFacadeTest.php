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

    /**
     * The facade used to swallow its own $depth argument, so every caller got
     * the tracer's default no matter what they asked for.
     */
    public function test_get_transaction_backtrace_forwards_the_depth(): void
    {
        $chain = [
            'tx1' => new TransactionData(txid: 'tx1', vin: [['txid' => 'tx2']]),
            'tx2' => new TransactionData(txid: 'tx2', vin: [['txid' => 'tx3']]),
            'tx3' => new TransactionData(txid: 'tx3', vin: [['txid' => 'tx4']]),
            'tx4' => new TransactionData(txid: 'tx4', vin: [['is_coinbase' => true]]),
        ];

        $facade = $this->facadeTracingChain($chain);

        self::assertSame(['tx1', 'tx2'], $this->txids($facade->getTransactionBacktrace('tx1', 2)));
        self::assertSame(['tx1', 'tx2', 'tx3'], $this->txids($facade->getTransactionBacktrace('tx1', 3)));
    }

    /**
     * The pass-through must not change what the one production caller
     * (AdditionalContextBuilder) already gets by omitting the argument.
     */
    public function test_omitting_the_depth_still_walks_the_whole_chain(): void
    {
        $chain = [
            'tx1' => new TransactionData(txid: 'tx1', vin: [['txid' => 'tx2']]),
            'tx2' => new TransactionData(txid: 'tx2', vin: [['txid' => 'tx3']]),
            'tx3' => new TransactionData(txid: 'tx3', vin: [['is_coinbase' => true]]),
        ];

        $facade = $this->facadeTracingChain($chain);

        self::assertSame(['tx1', 'tx2', 'tx3'], $this->txids($facade->getTransactionBacktrace('tx1')));
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

    /**
     * @param array<string, TransactionData> $chain
     */
    private function facadeTracingChain(array $chain): UtxoTraceFacade
    {
        $blockchainFacade = new class($chain) implements BlockchainFacadeInterface {
            /**
             * @param array<string, TransactionData> $chain
             */
            public function __construct(private readonly array $chain)
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
                return BlockchainData::forTransaction($this->chain[$input->text]);
            }
        };

        $logger = self::createStub(LoggerInterface::class);

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

        return new UtxoTraceFacade($utxoTracer, new TransactionTracer($blockchainFacade, $logger));
    }

    /**
     * @param list<TransactionData> $trace
     *
     * @return list<string>
     */
    private function txids(array $trace): array
    {
        return array_map(static fn (TransactionData $tx): string => $tx->txid, $trace);
    }
}
