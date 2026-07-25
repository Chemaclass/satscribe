<?php

declare(strict_types=1);

namespace Modules\Chat\Application;

use App\Models\Chat;
use App\Models\Message;
use Illuminate\Pagination\Paginator;
use Modules\Chat\Domain\Exception\MessageNotFound;
use Modules\Chat\Domain\Exception\RawMessageNotVisible;
use Modules\Chat\Domain\Repository\ChatRepositoryInterface;
use Modules\Chat\Domain\Repository\MessageRepositoryInterface;
use Modules\Chat\Domain\ViewModel\HistoryChatItem;
use Psr\Log\LoggerInterface;

use function is_array;

final readonly class HistoryService
{
    public function __construct(
        private ChatRepositoryInterface $repository,
        private MessageRepositoryInterface $messageRepository,
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
     * The id is a bare integer straight from the URL, so without the visibility
     * check below a private chat's stored payload was readable by anyone
     * counting upwards.
     *
     *
     * @throws MessageNotFound         when no such message exists
     * @throws RawMessageNotVisible    when the caller may not see its chat
     *
     * @return array<string, mixed>|null
     */
    public function getRawMessageData(int $messageId, string $trackingId): ?array
    {
        $this->logger->debug('Fetching raw message data', ['message_id' => $messageId]);

        $message = $this->messageRepository->findWithChat($messageId);

        if (!$message instanceof Message) {
            throw MessageNotFound::withId($messageId);
        }

        $chat = $message->chat;

        if (!$chat instanceof Chat || !$chat->canShow($trackingId)) {
            throw RawMessageNotVisible::withId($messageId);
        }

        $raw = $message->meta['raw_data'] ?? null;

        $this->logger->debug('Raw message data fetched', ['exists' => $raw !== null]);

        return is_array($raw) ? $raw : null;
    }
}
