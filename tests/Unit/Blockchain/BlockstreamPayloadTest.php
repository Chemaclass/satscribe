<?php

declare(strict_types=1);

namespace Tests\Unit\Blockchain;

use Modules\Blockchain\Application\Blockstream\BlockstreamPayload;
use Modules\Blockchain\Domain\Exception\BlockchainException;
use PHPUnit\Framework\TestCase;

final class BlockstreamPayloadTest extends TestCase
{
    public function test_object_returns_the_body_when_every_field_matches(): void
    {
        $body = ['id' => 'abc', 'height' => 1, 'confirmed' => true, 'extra' => 'kept'];

        $result = BlockstreamPayload::object($body, 'block abc', [
            'id' => BlockstreamPayload::STRING,
            'height' => BlockstreamPayload::INT,
            'confirmed' => BlockstreamPayload::BOOL,
        ]);

        self::assertSame($body, $result);
    }

    public function test_object_rejects_a_non_array_body(): void
    {
        $this->expectException(BlockchainException::class);
        $this->expectExceptionMessage('Malformed Blockstream payload for block abc: not a JSON object');

        BlockstreamPayload::object('<html>502</html>', 'block abc', []);
    }

    public function test_object_rejects_a_missing_field(): void
    {
        $this->expectException(BlockchainException::class);
        $this->expectExceptionMessage('Malformed Blockstream payload for block abc: height');

        BlockstreamPayload::object(['id' => 'abc'], 'block abc', [
            'id' => BlockstreamPayload::STRING,
            'height' => BlockstreamPayload::INT,
        ]);
    }

    /**
     * Blockstream sends heights as JSON numbers; a numeric string would mean
     * the body is not what the caller thinks it is.
     */
    public function test_object_rejects_a_field_of_the_wrong_type(): void
    {
        $this->expectException(BlockchainException::class);
        $this->expectExceptionMessage('Malformed Blockstream payload for block abc: height');

        BlockstreamPayload::object(['height' => '840000'], 'block abc', [
            'height' => BlockstreamPayload::INT,
        ]);
    }

    public function test_object_list_returns_a_list_of_objects(): void
    {
        $body = [['txid' => 'a'], ['txid' => 'b']];

        self::assertSame($body, BlockstreamPayload::objectList($body, 'txs of abc'));
    }

    public function test_object_list_rejects_a_non_object_entry(): void
    {
        $this->expectException(BlockchainException::class);
        $this->expectExceptionMessage('Malformed Blockstream payload for txs of abc: entry 1');

        BlockstreamPayload::objectList([['txid' => 'a'], 'nope'], 'txs of abc');
    }
}
