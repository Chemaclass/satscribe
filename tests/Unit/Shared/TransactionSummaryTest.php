<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use Modules\Shared\Domain\Data\Blockchain\TransactionData;
use Modules\Shared\Domain\Data\Blockchain\TransactionSummary;
use PHPUnit\Framework\TestCase;

/**
 * These numbers are fed verbatim into the AI prompt, so a silently wrong total
 * reads as fact to the user. Blockstream input/output objects are unsealed and
 * their inner keys vary by script type, which is exactly where garbage creeps
 * in unnoticed.
 */
final class TransactionSummaryTest extends TestCase
{
    public function test_totals_sum_the_input_and_output_values(): void
    {
        $summary = TransactionSummary::from($this->transaction(
            vin: [
                ['prevout' => ['value' => 1000, 'scriptpubkey_type' => 'v0_p2wpkh']],
                ['prevout' => ['value' => 500, 'scriptpubkey_type' => 'v0_p2wpkh']],
            ],
            vout: [
                ['value' => 1200, 'scriptpubkey_type' => 'v0_p2wpkh'],
                ['value' => 200, 'scriptpubkey_type' => 'op_return'],
            ],
        ));

        self::assertSame(1500, $summary->totalInput);
        self::assertSame(1400, $summary->totalOutput);
        self::assertSame(2, $summary->inputCount);
        self::assertSame(2, $summary->outputCount);
        self::assertTrue($summary->hasOpReturn);
    }

    public function test_wallet_types_are_counted_across_inputs_and_outputs(): void
    {
        $summary = TransactionSummary::from($this->transaction(
            vin: [['prevout' => ['scriptpubkey_type' => 'v0_p2wpkh']]],
            vout: [
                ['scriptpubkey_type' => 'v0_p2wpkh'],
                ['scriptpubkey_type' => 'v1_p2tr'],
            ],
        ));

        self::assertSame(['v0_p2wpkh' => 2, 'v1_p2tr' => 1], $summary->walletTypes);
    }

    /**
     * A prevout that is not an object, or a value that is not a number, used to
     * be summed anyway — string offset access and numeric coercion turned it
     * into a plausible-looking total rather than being skipped.
     */
    public function test_malformed_inputs_and_outputs_are_ignored_in_the_totals(): void
    {
        $summary = TransactionSummary::from($this->transaction(
            vin: [
                ['prevout' => ['value' => 1000]],
                ['prevout' => 'not-an-object'],
                ['prevout' => ['value' => 'not-a-number']],
                ['no_prevout' => true],
            ],
            vout: [
                ['value' => 900],
                ['value' => ['nested']],
                ['no_value' => true],
            ],
        ));

        self::assertSame(1000, $summary->totalInput);
        self::assertSame(900, $summary->totalOutput);
    }

    public function test_a_coinjoin_like_shape_is_flagged(): void
    {
        $vin = [];
        for ($i = 0; $i < 6; ++$i) {
            $vin[] = ['prevout' => ['value' => 1000, 'scriptpubkey_address' => "addr{$i}"]];
        }

        $summary = TransactionSummary::from($this->transaction(
            vin: $vin,
            vout: [
                ['value' => 900],
                ['value' => 900],
                ['value' => 900],
            ],
        ));

        self::assertTrue($summary->isCoinJoinLike);
    }

    public function test_a_consolidation_like_shape_is_flagged(): void
    {
        $vin = array_fill(0, 6, ['prevout' => ['value' => 100]]);

        $summary = TransactionSummary::from($this->transaction(vin: $vin, vout: [['value' => 550]]));

        self::assertTrue($summary->isConsolidationLike);
    }

    /**
     * @param list<array<string, mixed>> $vin
     * @param list<array<string, mixed>> $vout
     */
    private function transaction(array $vin = [], array $vout = []): TransactionData
    {
        return new TransactionData(
            txid: 'tx',
            vin: $vin,
            vout: $vout,
            fee: 100,
            confirmed: true,
            blockHeight: 840000,
        );
    }
}
