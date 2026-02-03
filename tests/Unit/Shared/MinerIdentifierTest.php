<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use Modules\Shared\Domain\Data\Blockchain\MinerIdentifier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MinerIdentifierTest extends TestCase
{
    public function test_detects_known_pool(): void
    {
        $hex = '666f6f414e54504f4f4c626172'; // fooANTPOOLbar
        $this->assertSame('AntPool', MinerIdentifier::extractFromCoinbaseHex($hex));
    }

    public function test_returns_ascii_when_unknown(): void
    {
        $hex = '666f6f626172'; // foobar
        $this->assertSame('FOOBAR', MinerIdentifier::extractFromCoinbaseHex($hex));
    }

    #[DataProvider('poolsProvider')]
    public function test_detects_pool(string $poolTag, string $expectedName): void
    {
        // Convert pool tag to hex (with prefix and suffix)
        $hex = bin2hex("prefix{$poolTag}suffix");
        $this->assertSame($expectedName, MinerIdentifier::extractFromCoinbaseHex($hex));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function poolsProvider(): array
    {
        return [
            'F2Pool' => ['F2Pool', 'F2Pool'],
            'ViaBTC' => ['ViaBTC', 'ViaBTC'],
            'Foundry' => ['Foundry', 'Foundry USA'],
            'Binance Pool' => ['Binance', 'Binance Pool'],
            'BTC.com' => ['BTC.COM', 'BTC.com Pool'],
            'MARA Pool' => ['MARA', 'MARA Pool'],
            'Ocean Pool' => ['OCEAN', 'Ocean Pool'],
            'Luxor' => ['LUXOR', 'Luxor'],
            'Braiins Pool' => ['BRAIINS', 'Braiins Pool (Slush successor)'],
            'SpiderPool' => ['SpiderPool', 'SpiderPool'],
        ];
    }

    public function test_handles_empty_hex(): void
    {
        $result = MinerIdentifier::extractFromCoinbaseHex('');
        $this->assertSame('', $result);
    }

    public function test_case_insensitive_matching(): void
    {
        // lowercase antpool
        $hex = bin2hex('antpool');
        $this->assertSame('AntPool', MinerIdentifier::extractFromCoinbaseHex($hex));
    }
}
