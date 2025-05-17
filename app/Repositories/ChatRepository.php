<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Data\BlockchainDataInterface;
use App\Data\PromptInput;
use App\Enums\PromptPersona;
use App\Enums\PromptType;
use App\Models\Chat;
use Illuminate\Contracts\Pagination\Paginator;

final readonly class ChatRepository
{
    public function __construct(
        private int $perPage,
        private string $ip,
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
            ->where('creator_ip', '=', $this->ip)
            ->whereHas('messages', function ($q) use ($input, $persona, $question): void {
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
        string $question = ''
    ): Chat {
        $raw = $blockchainData->toArray();

        $forceRefresh = $input->type->value === PromptType::Transaction->value
            && isset($raw['status']['confirmed'])
            && $raw['status']['confirmed'] === false;

        /** @var Chat $chat */
        $chat = Chat::create([
            'title' => ucfirst($input->type->value).':'.$input->text,
            'creator_ip' => $this->ip,
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
            'input' => $userMessage,
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

        if (!$showAll) {
            $query->where('creator_ip', $this->ip);
        }

        return $query->latest()
            ->simplePaginate($this->perPage);
    }
}
