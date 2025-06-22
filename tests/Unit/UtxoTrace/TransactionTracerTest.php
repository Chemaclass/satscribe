<?php

declare(strict_types=1);

namespace Tests\Unit\UtxoTrace;

use Modules\Blockchain\Domain\BlockchainFacadeInterface;
use Modules\Shared\Domain\Data\Blockchain\BlockchainData;
use Modules\Shared\Domain\Data\Blockchain\TransactionData;
use Modules\Shared\Domain\Data\Chat\PromptInput;
use Modules\UtxoTrace\Application\Tracer\TransactionTracer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class TransactionTracerTest extends TestCase
{
    public function test_get_backtrace_traces_until_coinbase(): void
    {
        $tx1 = new TransactionData(txid: 'tx1', vin: [['is_coinbase' => true]]);
        $tx2 = new TransactionData(txid: 'tx2', vin: [['txid' => 'tx1']]);
        $tx3 = new TransactionData(txid: 'tx3', vin: [['txid' => 'tx2']]);

        $map = [
            'tx3' => BlockchainData::forTransaction($tx3),
            'tx2' => BlockchainData::forTransaction($tx2),
            'tx1' => BlockchainData::forTransaction($tx1),
        ];

        $facade = new class($map) implements BlockchainFacadeInterface {
            public function __construct(private readonly array $map)
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
                return $this->map[$input->text];
            }
        };

        $logger = self::createStub(LoggerInterface::class);

        $service = new TransactionTracer($facade, $logger);

        $result = $service->getBacktrace('tx3');

        $this->assertSame([$tx3, $tx2, $tx1], $result);
    }

    public function test_get_backtrace_returns_empty_on_failure(): void
    {
        $facade = new class() implements BlockchainFacadeInterface {
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
                throw new RuntimeException('fail');
            }
        };

        $logger = self::createStub(LoggerInterface::class);

        $service = new TransactionTracer($facade, $logger);

        $this->assertSame([], $service->getBacktrace('tx'));
    }
}
