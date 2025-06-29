<?php

declare(strict_types=1);

namespace Modules\Chat\Application;

use App\Models\Message;
use Illuminate\Contracts\Pagination\Paginator;
use Modules\Chat\Domain\Repository\ChatRepositoryInterface;
use Psr\Log\LoggerInterface;

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
        $this->logger->debug('Fetching chat history', ['all' => $showAll]);

        $pagination = $this->repository->getPagination($showAll);

        $this->logger->debug('Chat history fetched');

        return $pagination;
    }

    /**
     * Get raw metadata for a message.
     */
    public function getRawMessageData(int $messageId): ?array
    {
        $this->logger->debug('Fetching raw message data', ['message_id' => $messageId]);

        $message = Message::find($messageId);

        $raw = $message->meta['raw_data'] ?? null;

        $this->logger->debug('Raw message data fetched', ['exists' => $raw !== null]);

        return $raw;
    }
}
