<?php

declare(strict_types=1);

namespace Modules\Chat\Domain\ViewModel;

use App\Models\Chat;
use Carbon\Carbon;
use RuntimeException;

use function is_array;
use function is_string;

final readonly class HistoryChatItem
{
    public function __construct(
        public string $ulid,
        public bool $isPublic,
        public bool $isShared,
        public bool $owned,
        public string $type,
        public string $input,
        public string $userMessage,
        public string $assistantMessage,
        public int $assistantMessageId,
        public bool $isBlock,
        public string $mempoolUrl,
        public Carbon $createdAt,
    ) {
    }

    public static function fromChat(Chat $chat, string $currentTrackingId): self
    {
        $chat->loadMissing('messages');

        $userMsg = $chat->getFirstUserMessage();
        $assistantMsg = $chat->getFirstAssistantMessage();
        $raw = $assistantMsg->rawData ?? [];

        // Eloquent types timestamps as nullable; a persisted chat always has one.
        $createdAt = $chat->created_at;
        if ($createdAt === null) {
            throw new RuntimeException("Chat {$chat->ulid} has no created_at timestamp.");
        }

        // raw_data is a JSON column, so its contents are whatever was stored.
        // A non-string identifier used to be concatenated anyway and rendered
        // a link to /block/Array.
        $isBlock = $assistantMsg->isBlock();
        $identifier = is_array($raw) ? ($raw[$isBlock ? 'hash' : 'txid'] ?? null) : null;
        $reference = is_string($identifier) ? $identifier : $assistantMsg->input;

        $mempoolUrl = $isBlock
            ? 'https://mempool.space/block/' . $reference
            : 'https://mempool.space/tx/' . $reference;

        return new self(
            ulid: $chat->ulid,
            isPublic: $chat->is_public,
            isShared: $chat->is_shared,
            owned: $chat->tracking_id === $currentTrackingId,
            type: $chat->type,
            input: $chat->input,
            userMessage: $userMsg->content,
            assistantMessage: $assistantMsg->content,
            assistantMessageId: $assistantMsg->id,
            isBlock: $assistantMsg->isBlock(),
            mempoolUrl: $mempoolUrl,
            createdAt: $createdAt,
        );
    }
}
