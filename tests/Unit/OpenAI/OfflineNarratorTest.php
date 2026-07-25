<?php

declare(strict_types=1);

namespace Tests\Unit\OpenAI;

use Modules\OpenAI\Application\OfflineNarrator;
use Modules\Shared\Domain\Data\Blockchain\BlockchainData;
use Modules\Shared\Domain\Data\Blockchain\BlockData;
use Modules\Shared\Domain\Data\Blockchain\TransactionData;
use Modules\Shared\Domain\Enum\Chat\PromptPersona;
use PHPUnit\Framework\TestCase;

/**
 * The fallback exists so a visitor is never left staring at an error, but it
 * must never be mistaken for the model talking, and it must never state a fact
 * that was not fetched.
 */
final class OfflineNarratorTest extends TestCase
{
    public function test_it_says_plainly_that_no_model_wrote_it(): void
    {
        $text = $this->narrate($this->block());

        self::assertStringContainsString('No AI model was reachable', $text);
        self::assertStringContainsString('none of it was written by a model', $text);
    }

    public function test_it_reports_the_real_block_figures(): void
    {
        $text = $this->narrate($this->block());

        self::assertStringContainsString('210,000', $text);
        self::assertStringContainsString('3 transactions', $text);
        self::assertStringContainsString('1,234', $text);
        self::assertStringContainsString('abc123def456', $text);
    }

    public function test_it_quotes_a_coinbase_message_when_there_is_one(): void
    {
        $text = $this->narrate($this->block(coinbase: 'Hello from 2012'));

        self::assertStringContainsString('Hello from 2012', $text);
    }

    public function test_it_omits_the_coinbase_line_when_there_is_none(): void
    {
        self::assertStringNotContainsString('coinbase', $this->narrate($this->block()));
    }

    public function test_each_persona_opens_differently(): void
    {
        $openings = array_map(
            fn (PromptPersona $p): string => (string) strtok($this->narrate($this->block(), $p), '.'),
            PromptPersona::cases(),
        );

        self::assertSame($openings, array_unique($openings), 'Personas should not read identically.');
    }

    public function test_an_unconfirmed_transaction_is_described_as_pending(): void
    {
        $data = BlockchainData::forTransaction(new TransactionData(
            txid: str_repeat('a', 64),
            vin: [[], []],
            vout: [[]],
            weight: 560,
            fee: 1500,
            confirmed: false,
        ));

        $text = $this->narrate($data);

        self::assertStringContainsString('mempool', $text);
        self::assertStringContainsString('2 inputs', $text);
        self::assertStringContainsString('1 output', $text);
        self::assertStringContainsString('1,500 sats', $text);
    }

    public function test_a_confirmed_transaction_names_its_block(): void
    {
        $data = BlockchainData::forTransaction(new TransactionData(
            txid: str_repeat('a', 64),
            confirmed: true,
            blockHeight: 840000,
        ));

        self::assertStringContainsString('840,000', $this->narrate($data));
    }

    private function narrate(BlockchainData $data, ?PromptPersona $persona = null): string
    {
        return (new OfflineNarrator())->narrate($data, $persona ?? PromptPersona::Educator);
    }

    private function block(?string $coinbase = null): BlockchainData
    {
        return BlockchainData::forBlock(new BlockData(
            hash: 'abc123def456789',
            height: 210000,
            timestamp: 1354116278,
            txCount: 3,
            size: 1234,
            weight: 4936,
            coinbaseMessage: $coinbase,
        ));
    }
}
