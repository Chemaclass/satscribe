<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Message;
use App\Repositories\ChatRepositoryInterface;
use Illuminate\Contracts\Pagination\Paginator;

final readonly class HistoryService
{
    public function __construct(private ChatRepositoryInterface $repository)
    {
    }

    /**
     * Retrieve paginated chats for history view.
     */
    public function getHistory(bool $showAll): Paginator
    {
        return $this->repository->getPagination($showAll);
    }

    /**
     * Get raw metadata for a message.
     */
    public function getRawMessageData(int $messageId): ?array
    {
        $message = Message::find($messageId);

        return $message->meta['raw_data'] ?? null;
    }
}
