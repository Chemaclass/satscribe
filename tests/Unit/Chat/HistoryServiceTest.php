<?php

declare(strict_types=1);

namespace Tests\Unit\Chat;

use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\Paginator;
use Modules\Chat\Application\HistoryService;
use Modules\Chat\Domain\Repository\ChatRepositoryInterface;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

final class HistoryServiceTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_get_history_passes_show_all_flag(): void
    {
        $repo = $this->createMock(ChatRepositoryInterface::class);
        $paginator = $this->createStub(Paginator::class);
        $repo->expects($this->once())
            ->method('getPagination')
            ->with(true)
            ->willReturn($paginator);

        $logger = $this->createStub(LoggerInterface::class);

        $service = new HistoryService($repo, $logger);

        $service->getHistory(true);
    }

    public function test_get_raw_message_data_returns_raw_data(): void
    {
        $rawData = ['txid' => 'abc123', 'fee' => 1000];

        $chat = \App\Models\Chat::create(['ulid' => 'test-ulid', 'is_public' => true]);
        $message = Message::create([
            'chat_id' => $chat->id,
            'role' => 'assistant',
            'content' => 'Response',
            'meta' => ['raw_data' => $rawData],
        ]);

        $repo = $this->createStub(ChatRepositoryInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $service = new HistoryService($repo, $logger);

        $result = $service->getRawMessageData($message->id);

        $this->assertSame($rawData, $result);
    }

    public function test_get_raw_message_data_returns_null_when_no_raw_data(): void
    {
        $chat = \App\Models\Chat::create(['ulid' => 'test-ulid', 'is_public' => true]);
        $message = Message::create([
            'chat_id' => $chat->id,
            'role' => 'assistant',
            'content' => 'Response',
            'meta' => [],
        ]);

        $repo = $this->createStub(ChatRepositoryInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $service = new HistoryService($repo, $logger);

        $result = $service->getRawMessageData($message->id);

        $this->assertNull($result);
    }
}
