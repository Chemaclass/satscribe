<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Services\MinerIdentifier;
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
}
