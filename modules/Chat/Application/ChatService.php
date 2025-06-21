<?php

declare(strict_types=1);

namespace Modules\Chat\Application;

use App\Data\PromptInput;
use App\Data\QuestionPlaceholder;
use App\Enums\PromptPersona;
use App\Exceptions\BlockchainException;
use App\Exceptions\OpenAIError;
use App\Http\Requests\HomeIndexRequest;
use App\Models\Chat;
use App\Models\Message;
use Modules\Blockchain\Application\BlockHeightProvider;

final readonly class ChatService
{
    public function __construct(
        private BlockHeightProvider $heightProvider,// @todo use Blockchain Facade instead
        private AddMessageAction $addMessageAction,
        private CreateChatAction $createChatAction,
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

    /**
     * Data required for the home page view.
     *
     * @return array<string, mixed>
     */
    public function getIndexData(): array
    {
        return [
            'questionPlaceholder' => QuestionPlaceholder::rand(),
            'suggestedPromptsGrouped' => QuestionPlaceholder::groupedPrompts(),
            'maxBitcoinBlockHeight' => $this->heightProvider->getMaxPossibleBlockHeight(),
            'personaDescriptions' => PromptPersona::descriptions()->toJson(),
        ];
    }

    /**
     * Handle chat creation request and return data for JSON response.
     *
     * @return array<string, mixed>
     * @throws BlockchainException|OpenAIError
     */
    public function createChat(HomeIndexRequest $request): array
    {
        $search = $this->getPromptInput($request);
        $persona = $this->getPromptPersona($request);
        $question = $request->getQuestionInput();
        $refreshEnabled = $request->isRefreshEnabled();
        $isPrivate = $request->isPrivate();

        $actionResult = $this->createChatAction->execute(
            $search,
            $persona,
            $question,
            $refreshEnabled,
            $isPrivate,
        );

        return [
            'maxBitcoinBlockHeight' => $this->heightProvider->getMaxPossibleBlockHeight(),
            'search' => $search,
            'chatUlid' => $actionResult->chat->ulid,
            'content' => $actionResult->chat->getLastAssistantMessage()->content,
            'html' => view('partials.chat-creation', [
                'chat' => $actionResult->chat,
                'suggestions' => $this->generateSuggestions($search),
            ])->render(),
        ];
    }

    private function getPromptInput(HomeIndexRequest $request): PromptInput
    {
        if ($request->hasSearchInput()) {
            return PromptInput::fromRaw($request->getSearchInput());
        }

        return PromptInput::fromRaw($this->heightProvider->getCurrentBlockHeight());
    }

    private function getPromptPersona(HomeIndexRequest $request): PromptPersona
    {
        return PromptPersona::tryFrom($request->getPersonaInput())
            ?? PromptPersona::from(PromptPersona::DEFAULT);
    }

    private function generateSuggestions(PromptInput $search): array
    {
        return $search->isBlock()
            ? QuestionPlaceholder::forBlock()
            : QuestionPlaceholder::forTx();
    }
}
