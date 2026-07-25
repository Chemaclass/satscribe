<?php

declare(strict_types=1);

namespace Modules\Chat\Application;

use App\Models\Chat;
use App\Models\Message;
use Illuminate\Pagination\Paginator;
use Modules\Chat\Domain\Repository\ChatRepositoryInterface;
use Modules\Chat\Domain\ViewModel\HistoryChatItem;
use Psr\Log\LoggerInterface;

final readonly class HistoryService
{
    public function __construct(
        private ChatRepositoryInterface $repository,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return Paginator<int, HistoryChatItem>
     */
    public function getHistory(bool $showAll): Paginator
    {
        $this->logger->debug('Fetching chat history', ['all' => $showAll]);

        $trackingId = tracking_id();
        $pagination = $this->repository->getPagination($showAll)->through(
            static fn (Chat $chat) => HistoryChatItem::fromChat($chat, $trackingId),
        );

        $this->logger->debug('Chat history fetched');

        return $pagination;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRawMessageData(int $messageId): ?array
    {
        $this->logger->debug('Fetching raw message data', ['message_id' => $messageId]);

        $message = Message::find($messageId);

        $raw = $message instanceof Message ? ($message->meta['raw_data'] ?? null) : null;

        $this->logger->debug('Raw message data fetched', ['exists' => $raw !== null]);

        return $raw;
    }
}
