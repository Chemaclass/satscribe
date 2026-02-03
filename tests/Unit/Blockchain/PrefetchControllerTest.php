<?php

declare(strict_types=1);

namespace Tests\Unit\Blockchain;

use Illuminate\Http\Request;
use Modules\Blockchain\Domain\BlockchainFacadeInterface;
use Modules\Blockchain\Domain\Exception\BlockchainException;
use Modules\Blockchain\Infrastructure\Http\Controller\PrefetchController;
use Modules\Shared\Domain\Data\Blockchain\BlockchainData;
use Modules\Shared\Domain\Data\Blockchain\BlockData;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

final class PrefetchControllerTest extends TestCase
{
    public function test_prefetch_returns_ok_for_valid_block_height(): void
    {
        $blockData = new BlockData('hash', height: 100, merkleRoot: 'merkle');
        $blockchainData = BlockchainData::forBlock($blockData);

        $facade = $this->createMock(BlockchainFacadeInterface::class);
        $facade->expects($this->once())
            ->method('getBlockchainData')
            ->willReturn($blockchainData);

        $controller = new PrefetchController($facade);

        $request = new Request(['q' => '100']);
        $response = $controller->prefetch($request);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('ok', $data['status']);
        $this->assertSame('block', $data['type']);
    }

    public function test_prefetch_returns_error_for_missing_query(): void
    {
        $facade = $this->createStub(BlockchainFacadeInterface::class);
        $controller = new PrefetchController($facade);

        $request = new Request();
        $response = $controller->prefetch($request);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame('error', $data['status']);
    }

    public function test_prefetch_returns_not_found_for_invalid_input(): void
    {
        $facade = $this->createMock(BlockchainFacadeInterface::class);
        $facade->method('getBlockchainData')
            ->willThrowException(BlockchainException::blockOrTxFetchFailed('invalid'));

        $controller = new PrefetchController($facade);

        $request = new Request(['q' => 'abc123def456abc123def456abc123def456abc123def456abc123def456abc1']);
        $response = $controller->prefetch($request);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertSame('error', $data['status']);
    }
}
