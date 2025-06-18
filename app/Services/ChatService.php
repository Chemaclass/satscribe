<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\AddMessageAction;
use App\Data\QuestionPlaceholder;
use App\Enums\PromptPersona;
use App\Models\Chat;
use App\Models\Message;

final readonly class ChatService
{
    public function __construct(
        private BlockHeightProvider $heightProvider,
        private AddMessageAction $addMessageAction,
    ) {
    }

    /**
     * Prepare data for showing a chat.
     *
     * @return array<string, mixed>
     */
    public function getChatData(Chat $chat): array
    {
        $chat->load('messages');

        /** @var Message $firstMsg */
        $firstMsg = $chat->messages()->first();

        return [
            'questionPlaceholder' => QuestionPlaceholder::rand(),
            'suggestedPromptsGrouped' => QuestionPlaceholder::groupedPrompts(),
            'suggestions' => $firstMsg->isBlock() ? QuestionPlaceholder::forBlock() : QuestionPlaceholder::forTx(),
            'maxBitcoinBlockHeight' => $this->heightProvider->getMaxPossibleBlockHeight(),
            'personaDescriptions' => PromptPersona::descriptions()->toJson(),
            'question' => $chat->messages()->first()->content,
            'chat' => $chat,
            'search' => $firstMsg->meta['input'] ?? '',
            'persona' => $firstMsg->meta['persona'] ?? '',
        ];
    }

    /**
     * Add a new message to the chat and return response data.
     *
     * @return array<string, mixed>
     */
    public function addMessage(Chat $chat, string $message): array
    {
        $this->addMessageAction->execute($chat, $message);

        return [
            'content' => $chat->getLastAssistantMessage()->content,
            'suggestions' => $chat->isBlock() ? QuestionPlaceholder::forBlock() : QuestionPlaceholder::forTx(),
        ];
    }
}
