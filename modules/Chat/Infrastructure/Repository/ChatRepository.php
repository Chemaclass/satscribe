<?php

declare(strict_types=1);

namespace Modules\Chat\Infrastructure\Repository;

use App\Models\Chat;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Chat\Domain\Repository\ChatRepositoryInterface;
use Modules\Shared\Domain\Data\Blockchain\BlockchainDataInterface;
use Modules\Shared\Domain\Data\Chat\PromptInput;
use Modules\Shared\Domain\Enum\Chat\PromptPersona;
use Modules\Shared\Domain\Enum\Chat\PromptType;

final readonly class ChatRepository implements ChatRepositoryInterface
{
    public function __construct(
        private int $perPage,
        private string $trackingId,
    ) {
    }

    /**
     * Find a chat matching given details or return null.
     */
    public function findByCriteria(
        PromptInput $input,
        PromptPersona $persona,
        string $question = '',
    ): ?Chat {
        // Find an existing chat by input type, input, persona, and question (legacy compatibility)
        return Chat::query()
            ->where('tracking_id', '=', $this->trackingId)
            ->whereHas('messages', static function ($q) use ($input, $persona, $question): void {
                $q->where('role', 'user')
                    ->where('content', $question)
                    ->whereJsonContains('meta->type', $input->type->value)
                    ->whereJsonContains('meta->input', $input->text)
                    ->whereJsonContains('meta->persona', $persona->value);
            })->first();
    }

    /**
     * Create a chat and attach user & assistant messages using the legacy pattern.
     */
    public function createChat(
        PromptInput $input,
        string $aiResponse,
        BlockchainDataInterface $blockchainData,
        PromptPersona $persona,
        string $question,
        bool $isPublic,
    ): Chat {
        $raw = $blockchainData->toArray();

        $forceRefresh = $input->type->value === PromptType::Transaction->value
            && isset($raw['status']['confirmed'])
            && $raw['status']['confirmed'] === false;

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

    public function getPagination(bool $showAll): Paginator
    {
        $query = Chat::query();

        if ($showAll) {
            $query->where(function (Builder $q): void {
                $q->where('tracking_id', $this->trackingId)
                    ->orWhere('is_public', true);
            });
        } else {
            $query->where('tracking_id', $this->trackingId);
        }

        return $query->latest()
            ->simplePaginate($this->perPage);
    }

    public function getTotalChats(): int
    {
        return Chat::count();
    }
}
