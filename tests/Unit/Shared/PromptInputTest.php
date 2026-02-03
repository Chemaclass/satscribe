<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use Modules\Shared\Domain\Data\Chat\PromptInput;
use Modules\Shared\Domain\Enum\Chat\PromptType;
use PHPUnit\Framework\TestCase;

final class PromptInputTest extends TestCase
{
    public function test_from_raw_creates_transaction_for_hex_string(): void
    {
        $txid = 'abc123def456abc123def456abc123def456abc123def456abc123def456abc1';

        $result = PromptInput::fromRaw($txid);

        $this->assertSame(PromptType::Transaction, $result->type);
        $this->assertSame($txid, $result->text);
    }

    public function test_from_raw_creates_block_for_numeric_string(): void
    {
        $result = PromptInput::fromRaw('800000');

        $this->assertSame(PromptType::Block, $result->type);
        $this->assertSame('800000', $result->text);
    }

    public function test_from_raw_creates_block_for_integer(): void
    {
        $result = PromptInput::fromRaw(800000);

        $this->assertSame(PromptType::Block, $result->type);
        $this->assertSame('800000', $result->text);
    }

    public function test_from_raw_creates_block_for_block_hash(): void
    {
        // Block hashes start with many zeros (8+ zeros followed by 56 hex chars = 64 total)
        // 8 zeros + 56 hex chars = 64 chars total
        $blockHash = '00000000abc123def456abc123def456abc123def456abc123def456abc123ff';

        $result = PromptInput::fromRaw($blockHash);

        $this->assertSame(PromptType::Block, $result->type);
        $this->assertSame($blockHash, $result->text);
    }

    public function test_is_block_returns_true_for_block_type(): void
    {
        $input = new PromptInput(PromptType::Block, '800000');

        $this->assertTrue($input->isBlock());
        $this->assertFalse($input->isTransaction());
    }

    public function test_is_transaction_returns_true_for_transaction_type(): void
    {
        $input = new PromptInput(PromptType::Transaction, 'txid123');

        $this->assertTrue($input->isTransaction());
        $this->assertFalse($input->isBlock());
    }

    public function test_constructor_sets_properties(): void
    {
        $input = new PromptInput(PromptType::Transaction, 'abc123');

        $this->assertSame(PromptType::Transaction, $input->type);
        $this->assertSame('abc123', $input->text);
    }

    public function test_from_raw_creates_transaction_for_non_numeric_non_blockhash(): void
    {
        $result = PromptInput::fromRaw('random-text');

        $this->assertSame(PromptType::Transaction, $result->type);
    }
}
