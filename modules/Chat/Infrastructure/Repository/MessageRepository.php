<?php

declare(strict_types=1);

namespace Modules\Chat\Infrastructure\Repository;

use App\Models\Message;
use Modules\Chat\Domain\Repository\MessageRepositoryInterface;
use Modules\Shared\Domain\Data\Chat\PromptInput;
use Modules\Shared\Domain\Enum\Chat\PromptPersona;

final readonly class MessageRepository implements MessageRepositoryInterface
{
    /**
     * How many rows to consider before giving up on finding a usable one. A key
     * rarely has more than a couple of matches, so this only bounds the damage
     * if one ever does.
     */
    private const CANDIDATE_LIMIT = 10;

    public function findAssistantMessage(PromptInput $input, PromptPersona $persona, string $question): ?Message
    {
        $candidates = Message::query()
            ->where('role', 'assistant')
            ->whereJsonContains('meta->type', $input->type->value)
            ->whereJsonContains('meta->input', $input->text)
            ->whereJsonContains('meta->persona', $persona->value)
            ->whereJsonContains('meta->question', $question)
            // Newest first: several rows can share a key, and an unordered
            // first() served whichever the database happened to return, so the
            // cached reply could change between requests.
            ->orderByDesc('id')
            ->limit(self::CANDIDATE_LIMIT)
            ->get();

        // A blank answer is not a usable cache hit: callers replay this content
        // directly and return before the empty-response guard runs, and chats
        // saved before that guard existed still hold empty assistant messages.
        // Filtered here rather than in SQL because trimming differs by driver —
        // SQLite's TRIM() leaves newlines alone — and this has to agree with the
        // guard, which uses trim().
        foreach ($candidates as $message) {
            if (trim((string) $message->content) !== '') {
                return $message;
            }
        }

        return null;
    }

    public function findWithChat(int $messageId): ?Message
    {
        return Message::with('chat')->find($messageId);
    }

    public function countAll(): int
    {
        return Message::count();
    }
}
