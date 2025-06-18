<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\CreateChatAction;
use App\Data\PromptInput;
use App\Data\QuestionPlaceholder;
use App\Enums\PromptPersona;
use App\Exceptions\BlockchainException;
use App\Exceptions\OpenAIError;
use App\Http\Requests\HomeIndexRequest;

final readonly class HomeService
{
    public function __construct(
        private BlockHeightProvider $heightProvider,
        private CreateChatAction $createChatAction,
    ) {
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
