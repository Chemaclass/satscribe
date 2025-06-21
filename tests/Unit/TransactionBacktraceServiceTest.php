<?php

declare(strict_types=1);

namespace Tests\Unit;

use Modules\Blockchain\Domain\BlockchainServiceInterface;
use Modules\Blockchain\Domain\Data\TransactionData;
use Modules\UtxoTrace\Application\TransactionBacktraceService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class TransactionBacktraceServiceTest extends TestCase
{
    public function test_format_for_prompt(): void
    {
        $service = new TransactionBacktraceService(
            self::createStub(BlockchainServiceInterface::class),
            self::createStub(LoggerInterface::class)
        );

        $tx1 = new TransactionData('tx1', 1, 0, [['is_coinbase' => true]], [], 0, 0, 0, true, 1, 'h1', 1);
        $tx2 = new TransactionData('tx2', 1, 0, [['txid' => 'tx1', 'is_coinbase' => false]], [], 0, 0, 0, true, 1, 'h1', 1);

        $result = $service->formatForPrompt([$tx2, $tx1]);

        $expected = "Transaction Backtrace\n1. tx2\n2. tx1";
        $this->assertSame($expected, $result);
    }
}
