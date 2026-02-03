<?php

declare(strict_types=1);

namespace Tests\Unit\Blockchain;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\Response;
use Modules\Blockchain\Application\Blockstream\BlockchainService;
use Modules\Blockchain\Domain\Exception\BlockchainException;
use Modules\Shared\Domain\Data\Chat\PromptInput;
use Modules\Shared\Domain\Enum\Chat\PromptType;
use Modules\Shared\Domain\HttpClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class BlockchainServiceTest extends TestCase
{
    public function test_get_blockchain_data_for_transaction(): void
    {
        $txid = 'abc123def456abc123def456abc123def456abc123def456abc123def456abc1';

        $txResponse = $this->createMock(Response::class);
        $txResponse->method('successful')->willReturn(true);
        $txResponse->method('json')->willReturn([
            'txid' => $txid,
            'version' => 2,
            'locktime' => 0,
            'vin' => [],
            'vout' => [],
            'size' => 200,
            'weight' => 800,
            'fee' => 1000,
        ]);

        $statusResponse = $this->createMock(Response::class);
        $statusResponse->method('successful')->willReturn(true);
        $statusResponse->method('json')->willReturn([
            'confirmed' => true,
            'block_height' => 800000,
            'block_hash' => 'blockhash123',
            'block_time' => 1700000000,
        ]);

        $blockResponse = $this->createMock(Response::class);
        $blockResponse->method('successful')->willReturn(true);
        $blockResponse->method('json')->willReturn([
            'id' => 'blockhash123',
            'height' => 800000,
            'version' => 0x20000000,
            'timestamp' => 1700000000,
            'tx_count' => 100,
            'size' => 1000000,
            'weight' => 4000000,
            'merkle_root' => 'merkle123',
            'previousblockhash' => 'prevhash',
            'mediantime' => 1699999000,
            'nonce' => 12345,
            'bits' => 386089497,
            'difficulty' => 1.0,
        ]);

        $http = $this->createMock(HttpClientInterface::class);
        $http->expects($this->exactly(3))
            ->method('get')
            ->willReturnOnConsecutiveCalls($txResponse, $statusResponse, $blockResponse);

        $logger = self::createStub(LoggerInterface::class);

        $service = new BlockchainService($http, $this->createPassthroughCache(), $logger);
        $input = new PromptInput(PromptType::Transaction, $txid);

        $result = $service->getBlockchainData($input);

        $this->assertSame($txid, $result->current()->toArray()['txid']);
    }

    public function test_get_blockchain_data_throws_on_tx_fetch_failure(): void
    {
        $txid = 'abc123def456abc123def456abc123def456abc123def456abc123def456abc1';

        $response = $this->createMock(Response::class);
        $response->method('successful')->willReturn(false);

        $http = $this->createMock(HttpClientInterface::class);
        $http->method('get')->willReturn($response);

        $logger = self::createStub(LoggerInterface::class);

        $service = new BlockchainService($http, $this->createPassthroughCache(), $logger);
        $input = new PromptInput(PromptType::Transaction, $txid);

        $this->expectException(BlockchainException::class);
        $service->getBlockchainData($input);
    }

    public function test_get_blockchain_data_for_block_by_height(): void
    {
        $blockHeight = '800000';
        $blockHash = 'blockhash123blockhash123blockhash123blockhash123blockhash12345';

        $heightResponse = $this->createMock(Response::class);
        $heightResponse->method('successful')->willReturn(true);
        $heightResponse->method('body')->willReturn($blockHash);

        $blockResponse = $this->createMock(Response::class);
        $blockResponse->method('successful')->willReturn(true);
        $blockResponse->method('json')->willReturn([
            'id' => $blockHash,
            'height' => 800000,
            'version' => 0x20000000,
            'timestamp' => 1700000000,
            'tx_count' => 100,
            'size' => 1000000,
            'weight' => 4000000,
            'merkle_root' => 'merkle123',
            'previousblockhash' => null, // No previous block
            'mediantime' => 1699999000,
            'nonce' => 12345,
            'bits' => 386089497,
            'difficulty' => 1.0,
        ]);

        $txsResponse = $this->createMock(Response::class);
        $txsResponse->method('successful')->willReturn(true);
        $txsResponse->method('json')->willReturn([]);

        // For next block height lookup (will fail - no next block)
        $nextHeightResponse = $this->createMock(Response::class);
        $nextHeightResponse->method('successful')->willReturn(false);

        $http = $this->createMock(HttpClientInterface::class);
        $http->method('get')
            ->willReturnOnConsecutiveCalls(
                $heightResponse,
                $blockResponse,
                $txsResponse,
                $nextHeightResponse,
            );

        $logger = self::createStub(LoggerInterface::class);

        $service = new BlockchainService($http, $this->createPassthroughCache(), $logger);
        $input = new PromptInput(PromptType::Block, $blockHeight);

        $result = $service->getBlockchainData($input);

        $this->assertSame(800000, $result->current()->toArray()['height']);
    }

    public function test_get_blockchain_data_for_unconfirmed_transaction(): void
    {
        $txid = 'abc123def456abc123def456abc123def456abc123def456abc123def456abc1';

        $txResponse = $this->createMock(Response::class);
        $txResponse->method('successful')->willReturn(true);
        $txResponse->method('json')->willReturn([
            'txid' => $txid,
            'version' => 2,
            'locktime' => 0,
            'vin' => [],
            'vout' => [],
            'size' => 200,
            'weight' => 800,
            'fee' => 1000,
        ]);

        $statusResponse = $this->createMock(Response::class);
        $statusResponse->method('successful')->willReturn(true);
        $statusResponse->method('json')->willReturn([
            'confirmed' => false,
        ]);

        $http = $this->createMock(HttpClientInterface::class);
        $http->expects($this->exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls($txResponse, $statusResponse);

        $logger = self::createStub(LoggerInterface::class);

        $service = new BlockchainService($http, $this->createPassthroughCache(), $logger);
        $input = new PromptInput(PromptType::Transaction, $txid);

        $result = $service->getBlockchainData($input);

        $this->assertFalse($result->current()->toArray()['status']['confirmed']);
    }
    private function createPassthroughCache(): CacheRepository
    {
        $cache = $this->createMock(CacheRepository::class);
        $cache->method('remember')->willReturnCallback(
            static fn (string $key, int $ttl, callable $callback) => $callback(),
        );

        return $cache;
    }
}
