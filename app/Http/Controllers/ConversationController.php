<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\QuestionPlaceholder;
use App\Enums\PromptPersona;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\BlockHeightProvider;
use Illuminate\View\View;

final readonly class ConversationController
{
    public function __construct(
        private BlockHeightProvider $heightProvider,
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
            'maxBitcoinBlockHeight' => $this->heightProvider->getMaxPossibleBlockHeight(),
            'personaDescriptions' => PromptPersona::descriptions()->toJson(),
            'question' => $conversation->messages()->first()->content,
            'conversation' => $conversation,
            'search' => $firstMsg->meta['input'] ?? '',
            'persona' => $firstMsg->meta['persona'] ?? '',
        ]);
    }
}
