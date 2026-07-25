<?php

declare(strict_types=1);

namespace Modules\Chat\Infrastructure\Repository;

use App\Models\Chat;
use App\Models\Message;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;
use Modules\Chat\Domain\Repository\ChatRepositoryInterface;
use Modules\Shared\Domain\Data\Blockchain\BlockchainDataInterface;
use Modules\Shared\Domain\Data\Chat\PromptInput;
use Modules\Shared\Domain\Enum\Chat\PromptPersona;
use Modules\Shared\Domain\Enum\Chat\PromptType;

use function is_array;

final readonly class ChatRepository implements ChatRepositoryInterface
{
    /**
     * How many matching chats to consider before giving up on a usable answer.
     * A key rarely has more than one, so this only bounds the work if it does.
     */
    private const CANDIDATE_LIMIT = 10;

    public function __construct(
        private int $perPage,
        private string $trackingId,
    ) {
    }

    public function findByCriteria(
        PromptInput $input,
        PromptPersona $persona,
        string $question = '',
    ): ?Chat {
        // Search criteria live in the first user message's meta JSON, not on the chat row.
        $candidates = Chat::query()
            ->where('tracking_id', '=', $this->trackingId)
            ->whereHas('messages', static function ($q) use ($input, $persona, $question): void {
                $q->where('role', 'user')
                    ->where('content', $question)
                    ->whereJsonContains('meta->type', $input->type->value)
                    ->whereJsonContains('meta->input', $input->text)
                    ->whereJsonContains('meta->persona', $persona->value);
            })
            ->with('messages')
            // Newest first, for the same reason as the message lookup: more
            // than one chat can match and the choice must not be arbitrary.
            ->orderByDesc('id')
            ->limit(self::CANDIDATE_LIMIT)
            ->get();

        // The caller replays a match as a finished result, so a chat whose
        // answer is blank is not a usable hit — the rows saved before the
        // empty-response guard existed are exactly that.
        foreach ($candidates as $chat) {
            if (trim($this->answerOf($chat)) !== '') {
                return $chat;
            }
        }

        return null;
    }

    public function createChat(
        PromptInput $input,
        string $aiResponse,
        BlockchainDataInterface $blockchainData,
        PromptPersona $persona,
        string $question,
        bool $isPublic,
    ): Chat {
        $raw = $blockchainData->toArray();

        // An unconfirmed transaction is still in the mempool, so its stored copy
        // goes stale as soon as it is mined.
        $status = $raw['status'] ?? null;

        $forceRefresh = $input->type === PromptType::Transaction
            && is_array($status)
            && ($status['confirmed'] ?? null) === false;

        /** @var Chat $chat */
        $chat = Chat::create([
            'title' => ucfirst($input->type->value) . ':' . $input->text,
            'tracking_id' => $this->trackingId,
            'is_public' => $isPublic,
            'is_shared' => false,
        ]);

        $chat->addUserMessage($question, [
            'type' => $input->type->value,
            'input' => $input->text,
            'persona' => $persona->value,
        ]);

        $chat->addAssistantMessage($aiResponse, [
            'type' => $input->type->value,
            'input' => $input->text,
            'persona' => $persona->value,
            'question' => $question,
            'raw_data' => $raw,
            'force_refresh' => $forceRefresh,
        ]);

        return $chat;
    }

    public function addMessageToChat(
        Chat $chat,
        string $userMessage,
        string $assistantResponse,
    ): void {
        $firstUserMsg = $chat->getFirstUserMessage();
        $firstAssistantMsg = $chat->getFirstAssistantMessage();

        $chat->addUserMessage($userMessage, [
            'type' => $firstUserMsg->type,
            'persona' => $firstUserMsg->persona,
            'input' => $firstUserMsg->input,
        ]);

        $chat->addAssistantMessage($assistantResponse, [
            'type' => $firstAssistantMsg->type,
            'input' => $firstAssistantMsg->input,
            'persona' => $firstAssistantMsg->persona,
            'raw_data' => [],
            'force_refresh' => false,
            'question' => $userMessage,
        ]);
    }

    /**
     * @return Paginator<int, Chat>
     */
    public function getPagination(bool $showAll): Paginator
    {
        $query = Chat::query()->with('messages');

        if ($showAll) {
            $query->where(function (Builder $q): void {
                $q->where('tracking_id', $this->trackingId)
                    ->orWhere('is_public', true);
            });
        } else {
            $query->where('tracking_id', $this->trackingId);
        }

        // timestamps() is second-precision, so chats made in the same second
        // tie on created_at. SQLite happens to break the tie consistently, but
        // nothing guarantees that, and an unstable order under LIMIT/OFFSET
        // repeats or skips rows between pages.
        return $query->latest()
            ->orderByDesc('id')
            ->simplePaginate($this->perPage);
    }

    public function setShared(Chat $chat, bool $shared): void
    {
        $chat->is_shared = $shared;
        $chat->save();
    }

    public function setPublic(Chat $chat, bool $isPublic): void
    {
        $chat->is_public = $isPublic;
        $chat->save();
    }

    private function answerOf(Chat $chat): string
    {
        $answer = $chat->messages->last(static fn (Message $m): bool => $m->role === 'assistant');

        return $answer instanceof Message ? (string) $answer->content : '';
    }
}
