<?php

declare(strict_types=1);

namespace Tests\Unit\UtxoTrace;

use Modules\Blockchain\Domain\BlockchainFacadeInterface;
use Modules\Shared\Domain\Data\Blockchain\TransactionData;
use Modules\UtxoTrace\Application\Tracer\TransactionTracer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class TransactionBacktraceServiceTest extends TestCase
{
    public function test_format_for_prompt(): void
    {
        $service = new TransactionTracer(
            self::createStub(BlockchainFacadeInterface::class),
            self::createStub(LoggerInterface::class),
        );

        $tx1 = new TransactionData(txid: 'tx1');
        $tx2 = new TransactionData(txid: 'tx2');

        $result = $service->formatForPrompt([$tx2, $tx1]);

        $expected = "Transaction Backtrace\n1. tx2\n2. tx1";
        $this->assertSame($expected, $result);
    }
}
