<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\SatscribeAction;
use App\Data\PromptInput;
use App\Data\QuestionPlaceholder;
use App\Enums\PromptPersona;
use App\Exceptions\BlockchainException;
use App\Exceptions\OpenAIError;
use App\Http\Requests\SatscribeIndexRequest;
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
            'maxBitcoinBlockHeight' => $this->heightProvider->getMaxPossibleBlockHeight(),
        ]);
    }

    public function submit(SatscribeIndexRequest $request, SatscribeAction $action): JsonResponse
    {
        $search = $this->getPromptInput($request);
        $persona = $this->getPromptPersona($request);
        $question = $request->getQuestionInput();
        $refreshEnabled = $request->isRefreshEnabled();

        try {
            $generatedPrompt = $action->execute($search, $persona, $refreshEnabled, $question);
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
            'html' => view('partials.description-result', [
                'result' => $generatedPrompt->result,
                'isFresh' => $generatedPrompt->isFresh,
                'search' => $search->text,
                'question' => $question,
                'persona' => $persona->value,
                'refreshed' => $refreshEnabled,
            ])->render()
        ]);
    }

    private function getPromptInput(SatscribeIndexRequest $request): PromptInput
    {
        if ($request->hasSearchInput()) {
            return PromptInput::fromRaw($request->getSearchInput());
        }

        return PromptInput::fromRaw($this->heightProvider->getCurrentBlockHeight());
    }

    private function getPromptPersona(SatscribeIndexRequest $request): PromptPersona
    {
        return PromptPersona::tryFrom($request->getPersonaInput())
            ?? PromptPersona::from(PromptPersona::DEFAULT);
    }
}
