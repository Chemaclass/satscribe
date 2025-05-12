<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AddMessageAction;
use App\Data\QuestionPlaceholder;
use App\Enums\PromptPersona;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\BlockHeightProvider;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;

final readonly class ConversationController
{
    public function __construct(
        private BlockHeightProvider $heightProvider,
        private AddMessageAction $addMessageAction,
    ) {
    }

    public function show(Conversation $conversation): View
    {
        $conversation->load('messages');

        /** @var Message $firstMsg */
        $firstMsg = $conversation->messages()->first();

        return view('home', [
            'questionPlaceholder' => QuestionPlaceholder::rand(),
            'suggestedPromptsGrouped' => QuestionPlaceholder::groupedPrompts(),
            'suggestions' => $firstMsg->isBlock() ? QuestionPlaceholder::forBlock() : QuestionPlaceholder::forTx(),
            'maxBitcoinBlockHeight' => $this->heightProvider->getMaxPossibleBlockHeight(),
            'personaDescriptions' => PromptPersona::descriptions()->toJson(),
            'question' => $conversation->messages()->first()->content,
            'conversation' => $conversation,
            'search' => $firstMsg->meta['input'] ?? '',
            'persona' => $firstMsg->meta['persona'] ?? '',
        ]);
    }

    public function addMessage(Request $request, Conversation $conversation): JsonResponse
    {
        $this->addMessageAction->execute($conversation, (string) $request->input('message'));

        $conversation->load('messages');

        return response()->json([
            'html' => view('partials.conversation', [
                'conversation' => $conversation,
                'suggestions' => $conversation->isBlock() ? QuestionPlaceholder::forBlock() : QuestionPlaceholder::forTx(),
            ])->render(),
        ]);
    }
}
