<?php

declare(strict_types=1);

namespace Tests\Unit\Chat;

use Modules\Blockchain\Domain\BlockchainFacadeInterface;
use Modules\Chat\Application\AdditionalContextBuilder;
use Modules\Shared\Domain\Data\Blockchain\BlockchainData;
use Modules\Shared\Domain\Data\Blockchain\BlockData;
use Modules\Shared\Domain\Data\Blockchain\TransactionData;
use Modules\Shared\Domain\Data\Chat\PromptInput;
use Modules\Shared\Domain\Enum\Chat\PromptType;
use Modules\UtxoTrace\Domain\UtxoTraceFacadeInterface;
use PHPUnit\Framework\TestCase;

final class AdditionalContextBuilderTest extends TestCase
{
    public function test_build_returns_empty_string_when_no_references(): void
    {
        $blockchainFacade = $this->createStub(BlockchainFacadeInterface::class);
        $utxoTraceFacade = $this->createStub(UtxoTraceFacadeInterface::class);

        $builder = new AdditionalContextBuilder($blockchainFacade, $utxoTraceFacade);

        $blockchainData = $this->createTransactionData();
        $input = new PromptInput(PromptType::Transaction, 'abc123');

        $result = $builder->build($blockchainData, $input, 'What is this transaction?');

        $this->assertSame('', $result);
    }

    public function test_build_extracts_referenced_transaction(): void
    {
        $referencedTxData = $this->createTransactionData('def456');

        $blockchainFacade = $this->createMock(BlockchainFacadeInterface::class);
        $blockchainFacade->expects($this->once())
            ->method('getBlockchainData')
            ->willReturn($referencedTxData);

        $utxoTraceFacade = $this->createStub(UtxoTraceFacadeInterface::class);

        $builder = new AdditionalContextBuilder($blockchainFacade, $utxoTraceFacade);

        $blockchainData = $this->createTransactionData('abc123');
        $input = new PromptInput(PromptType::Transaction, 'abc123');

        // Reference a different transaction in the question
        $result = $builder->build(
            $blockchainData,
            $input,
            'Compare with tx def456def456def456def456def456def456def456def456def456def456def4',
        );

        $this->assertStringContainsString('Referenced Transaction', $result);
    }

    public function test_build_does_not_extract_same_transaction(): void
    {
        $blockchainFacade = $this->createMock(BlockchainFacadeInterface::class);
        $blockchainFacade->expects($this->never())
            ->method('getBlockchainData');

        $utxoTraceFacade = $this->createStub(UtxoTraceFacadeInterface::class);

        $builder = new AdditionalContextBuilder($blockchainFacade, $utxoTraceFacade);

        $blockchainData = $this->createTransactionData('abc123abc123abc123abc123abc123abc123abc123abc123abc123abc123abc1');
        $input = new PromptInput(PromptType::Transaction, 'abc123abc123abc123abc123abc123abc123abc123abc123abc123abc123abc1');

        // Reference the same transaction
        $result = $builder->build(
            $blockchainData,
            $input,
            'What about tx abc123abc123abc123abc123abc123abc123abc123abc123abc123abc123abc1?',
        );

        $this->assertSame('', $result);
    }

    public function test_build_extracts_backtrace_for_transaction(): void
    {
        $blockchainFacade = $this->createStub(BlockchainFacadeInterface::class);

        $utxoTraceFacade = $this->createMock(UtxoTraceFacadeInterface::class);
        $utxoTraceFacade->expects($this->once())
            ->method('getTransactionBacktrace')
            ->with('abc123')
            ->willReturn([]);
        $utxoTraceFacade->expects($this->once())
            ->method('formatForPrompt')
            ->willReturn('Backtrace data');

        $builder = new AdditionalContextBuilder($blockchainFacade, $utxoTraceFacade);

        $blockchainData = $this->createTransactionData('abc123');
        $input = new PromptInput(PromptType::Transaction, 'abc123');

        $result = $builder->build($blockchainData, $input, 'Show me the backtrace');

        $this->assertStringContainsString('Backtrace data', $result);
    }

    public function test_build_extracts_backtrace_with_hyphen(): void
    {
        $blockchainFacade = $this->createStub(BlockchainFacadeInterface::class);

        $utxoTraceFacade = $this->createMock(UtxoTraceFacadeInterface::class);
        $utxoTraceFacade->expects($this->once())
            ->method('getTransactionBacktrace')
            ->willReturn([]);
        $utxoTraceFacade->method('formatForPrompt')->willReturn('Trace');

        $builder = new AdditionalContextBuilder($blockchainFacade, $utxoTraceFacade);

        $blockchainData = $this->createTransactionData('abc123');
        $input = new PromptInput(PromptType::Transaction, 'abc123');

        $result = $builder->build($blockchainData, $input, 'Show me the back-trace');

        $this->assertNotSame('', $result);
    }

    public function test_build_includes_previous_block_when_mentioned(): void
    {
        $blockchainFacade = $this->createStub(BlockchainFacadeInterface::class);
        $utxoTraceFacade = $this->createStub(UtxoTraceFacadeInterface::class);

        $builder = new AdditionalContextBuilder($blockchainFacade, $utxoTraceFacade);

        $currentBlock = $this->createSingleBlockData('currenthash');
        $previousBlock = $this->createSingleBlockData('prevhash');
        $blockchainData = BlockchainData::forBlock($currentBlock, $previousBlock, null);

        $input = new PromptInput(PromptType::Block, '800000');

        $result = $builder->build($blockchainData, $input, 'Compare with the previous block');

        $this->assertStringContainsString('Previous Block', $result);
    }

    public function test_build_includes_next_block_when_mentioned(): void
    {
        $blockchainFacade = $this->createStub(BlockchainFacadeInterface::class);
        $utxoTraceFacade = $this->createStub(UtxoTraceFacadeInterface::class);

        $builder = new AdditionalContextBuilder($blockchainFacade, $utxoTraceFacade);

        $currentBlock = $this->createSingleBlockData('currenthash');
        $nextBlock = $this->createSingleBlockData('nexthash');
        $blockchainData = BlockchainData::forBlock($currentBlock, null, $nextBlock);

        $input = new PromptInput(PromptType::Block, '800000');

        $result = $builder->build($blockchainData, $input, 'What about the next block?');

        $this->assertStringContainsString('Next Block', $result);
    }

    private function createTransactionData(string $txid = 'abc123'): BlockchainData
    {
        $tx = new TransactionData(
            txid: $txid,
            version: 2,
            locktime: 0,
            vin: [],
            vout: [],
            size: 200,
            weight: 800,
            fee: 1000,
            confirmed: true,
            blockHeight: 800000,
            blockHash: 'blockhash',
            blockTime: 1700000000,
        );

        return BlockchainData::forTransaction($tx, null);
    }

    private function createSingleBlockData(string $hash = 'blockhash'): BlockData
    {
        return new BlockData(
            hash: $hash,
            height: 800000,
            version: 0x20000000,
            timestamp: 1700000000,
            txCount: 100,
            size: 1000000,
            weight: 4000000,
            merkleRoot: 'merkle123',
            previousBlockHash: 'prevhash',
            medianTime: 1699999000,
            nonce: 12345,
            bits: 386089497,
            difficulty: 1.0,
            transactions: [],
        );
    }
}
