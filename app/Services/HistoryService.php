<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Message;
use App\Repositories\ChatRepositoryInterface;
use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Pagination\Paginator;

final readonly class HistoryService
{
    public function __construct(
        private ChatRepositoryInterface $repository,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Retrieve paginated chats for history view.
     */
    public function getHistory(bool $showAll): Paginator
    {
        $this->logger->info('Fetching chat history', ['all' => $showAll]);

        $pagination = $this->repository->getPagination($showAll);

        $this->logger->info('Chat history fetched');

        return $pagination;
    }

    /**
     * Get raw metadata for a message.
     */
    public function getRawMessageData(int $messageId): ?array
    {
        $this->logger->info('Fetching raw message data', ['message_id' => $messageId]);

        $message = Message::find($messageId);

        $raw = $message->meta['raw_data'] ?? null;

        $this->logger->info('Raw message data fetched', ['exists' => $raw !== null]);

        return $raw;
    }
}
