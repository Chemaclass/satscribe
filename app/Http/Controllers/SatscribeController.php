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

final readonly class SatscribeController
{
    public function __construct(
        private BlockHeightProvider $heightProvider,
    ) {
    }

    public function index(SatscribeIndexRequest $request, SatscribeAction $action): View
    {
        if (!$request->isSubmitted()) {
            return view('satscribe', [
                'questionPlaceholder' => QuestionPlaceholder::rand(),
                'maxBitcoinBlockHeight' => $this->heightProvider->getMaxPossibleBlockHeight(),
            ]);
        }

        $search = $this->getPromptInput($request);
        $persona = $this->getPromptPersona($request);
        $question = $request->getQuestionInput();
        $refresh = $request->isRefreshEnabled();

        try {
            $generatedPrompt = $action->execute($search, $persona, $refresh, $question);
        } catch (BlockchainException|OpenAIError $e) {
            Log::error('Failed to describe prompt result', [
                'search' => $search->text,
                'refresh' => $refresh,
                'question' => $question,
                'persona' => $persona->value,
                'error' => $e->getMessage(),
            ]);
            return view('satscribe')
                ->withErrors(['search' => $e->getMessage()]);
        }

        return view('satscribe', [
            'result' => $generatedPrompt->result,
            'isFresh' => $generatedPrompt->isFresh,
            'search' => $search->text,
            'question' => $question,
            'persona' => $persona->value,
            'refreshed' => $refresh,
            'questionPlaceholder' => QuestionPlaceholder::rand(),
            'maxBitcoinBlockHeight' => $this->heightProvider->getMaxPossibleBlockHeight(),
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
