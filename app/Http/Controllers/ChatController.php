<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AddMessageAction;
use App\Data\QuestionPlaceholder;
use App\Enums\PromptPersona;
use App\Models\Chat;
use App\Models\Message;
use App\Services\BlockHeightProvider;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;

final readonly class ChatController
{
    public function __construct(
        private BlockHeightProvider $heightProvider,
        private AddMessageAction $addMessageAction,
    ) {
    }

    public function show(Chat $chat): View
    {
        $chat->load('messages');

        /** @var Message $firstMsg */
        $firstMsg = $chat->messages()->first();

        return view('home', [
            'questionPlaceholder' => QuestionPlaceholder::rand(),
            'suggestedPromptsGrouped' => QuestionPlaceholder::groupedPrompts(),
            'suggestions' => $firstMsg->isBlock() ? QuestionPlaceholder::forBlock() : QuestionPlaceholder::forTx(),
            'maxBitcoinBlockHeight' => $this->heightProvider->getMaxPossibleBlockHeight(),
            'personaDescriptions' => PromptPersona::descriptions()->toJson(),
            'question' => $chat->messages()->first()->content,
            'chat' => $chat,
            'search' => $firstMsg->meta['input'] ?? '',
            'persona' => $firstMsg->meta['persona'] ?? '',
        ]);
    }

    public function addMessage(Request $request, Chat $chat): JsonResponse
    {
        if (client_ip() !== $chat->creator_ip) {
            abort(403, 'You are not allowed to send messages to this chat.');
        }

        $this->addMessageAction->execute($chat, (string) $request->input('message'));

        return response()->json([
            'content' => $chat->getLastAssistantMessage()->content,
            'suggestions' => $chat->isBlock() ? QuestionPlaceholder::forBlock() : QuestionPlaceholder::forTx(),
        ]);
    }
}
