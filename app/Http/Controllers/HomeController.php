<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateChatAction;
use App\Data\PromptInput;
use App\Data\QuestionPlaceholder;
use App\Enums\PromptPersona;
use App\Exceptions\BlockchainException;
use App\Exceptions\OpenAIError;
use App\Http\Requests\HomeIndexRequest;
use App\Services\BlockHeightProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class HomeController
{
    public function __construct(
        private BlockHeightProvider $heightProvider,
    ) {
    }

    public function index(): View
    {
        return view('home', [
            'questionPlaceholder' => QuestionPlaceholder::rand(),
            'suggestedPromptsGrouped' => QuestionPlaceholder::groupedPrompts(),
            'maxBitcoinBlockHeight' => $this->heightProvider->getMaxPossibleBlockHeight(),
            'personaDescriptions' => PromptPersona::descriptions()->toJson(),
        ]);
    }

    public function createChat(HomeIndexRequest $request, CreateChatAction $action): JsonResponse
    {
        $search = $this->getPromptInput($request);
        $persona = $this->getPromptPersona($request);
        $question = $request->getQuestionInput();
        $refreshEnabled = $request->isRefreshEnabled();
        $isPrivate = $request->isPrivate();

        try {
            $actionResult = $action->execute($search, $persona, $question, $refreshEnabled, $isPrivate);
        } catch (BlockchainException|OpenAIError $e) {
            Log::error('Failed to describe prompt result', [
                'search' => $search->text,
                'refreshEnabled' => $refreshEnabled,
                'question' => $question,
                'persona' => $persona->value,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'error' => $e->getMessage()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'maxBitcoinBlockHeight' => $this->heightProvider->getMaxPossibleBlockHeight(),
            'search' => $search,
            'chatUlid' => $actionResult->chat->ulid,
            'content' => $actionResult->chat->getLastAssistantMessage()->content,
            'html' => view('partials.chat-creation', [
                'chat' => $actionResult->chat,
                'suggestions' => $this->generateSuggestions($search),
            ])->render()
        ]);
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
        if ($search->isBlock()) {
            return QuestionPlaceholder::forBlock();
        }
        return QuestionPlaceholder::forTx();
    }
}
