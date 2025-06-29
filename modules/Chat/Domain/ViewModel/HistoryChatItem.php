<?php

declare(strict_types=1);

namespace Modules\Chat\Domain\ViewModel;

use App\Models\Chat;
use Carbon\Carbon;

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

        $mempoolUrl = $assistantMsg->isBlock()
            ? 'https://mempool.space/block/' . ($raw['hash'] ?? $assistantMsg->input)
            : 'https://mempool.space/tx/' . ($raw['txid'] ?? $assistantMsg->input);

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
            createdAt: $chat->created_at,
        );
    }
}
