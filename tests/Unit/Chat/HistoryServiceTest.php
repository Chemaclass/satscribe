<?php

declare(strict_types=1);

namespace Tests\Unit\Chat;

use Illuminate\Contracts\Pagination\Paginator;
use Modules\Chat\Application\HistoryService;
use Modules\Chat\Domain\Repository\ChatRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class HistoryServiceTest extends TestCase
{
    public function test_get_history_returns_paginator(): void
    {
        $repo = $this->createMock(ChatRepositoryInterface::class);
        $paginator = $this->createStub(Paginator::class);
        $repo->expects($this->once())
            ->method('getPagination')
            ->with(false)
            ->willReturn($paginator);

        $logger = $this->createStub(LoggerInterface::class);

        $service = new HistoryService($repo, $logger);

        $this->assertSame($paginator, $service->getHistory(false));
    }
}
