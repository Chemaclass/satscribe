<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use Modules\Shared\Domain\Data\Blockchain\BlockSummary;
use PHPUnit\Framework\TestCase;

/**
 * toPrompt() is what the model reads to describe a block, so the wallet-type
 * breakdown has to be keyed on the values Blockstream actually emits. Esplora
 * versions the SegWit and Taproot names — `v0_p2wpkh`, `v0_p2wsh`, `v1_p2tr` —
 * and those are the majority of outputs in a modern block.
 */
final class BlockSummaryTest extends TestCase
{
    public function test_the_versioned_segwit_and_taproot_types_are_described(): void
    {
        $prompt = $this->summaryWithWalletTypes([
            'v0_p2wpkh' => 20,
            'v1_p2tr' => 14,
            'v0_p2wsh' => 1,
        ])->toPrompt();

        self::assertStringContainsString('P2WPKH: Native SegWit', $prompt);
        self::assertStringContainsString('P2TR: Taproot', $prompt);
        self::assertStringContainsString('P2WSH: SegWit complex scripts', $prompt);
        self::assertStringNotContainsString('V0_P2WPKH', $prompt);
        self::assertStringNotContainsString('V1_P2TR', $prompt);
    }

    public function test_the_unversioned_types_are_described(): void
    {
        $prompt = $this->summaryWithWalletTypes([
            'p2pkh' => 17,
            'p2sh' => 13,
            'op_return' => 3,
            'multisig' => 1,
        ])->toPrompt();

        self::assertStringContainsString('P2PKH: Legacy', $prompt);
        self::assertStringContainsString('P2SH: Script', $prompt);
        self::assertStringContainsString('OP_RETURN: Data-carrying', $prompt);
        self::assertStringContainsString('P2MS: Multisig', $prompt);
    }

    public function test_an_unknown_type_falls_back_to_its_raw_name(): void
    {
        $prompt = $this->summaryWithWalletTypes(['v9_something' => 2])->toPrompt();

        self::assertStringContainsString('V9_SOMETHING', $prompt);
    }

    /**
     * @param array<string, int> $walletTypes
     */
    private function summaryWithWalletTypes(array $walletTypes): BlockSummary
    {
        return new BlockSummary(
            height: 210000,
            txCount: 3,
            size: 1000,
            weight: 4000,
            timestamp: 1300000000,
            miner: 'Pool',
            coinbaseValue: 5000000000,
            coinbaseMessage: null,
            hasOpReturnInCoinbase: false,
            topTransactionsByFee: [],
            walletTypesBreakdown: $walletTypes,
        );
    }
}
