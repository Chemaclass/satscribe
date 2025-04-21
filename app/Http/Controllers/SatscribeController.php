<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\SatscribeAction;
use App\Data\QuestionPlaceholder;
use App\Exceptions\BlockchainException;
use App\Exceptions\OpenAIError;
use App\Http\Requests\SatscribeIndexRequest;
use App\Services\BlockHeightProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

final class SatscribeController
{
    public function __construct(
        private readonly BlockHeightProvider $heightProvider,
    ) {
    }

    public function index(SatscribeIndexRequest $request, SatscribeAction $action): View
    {
        if (!$request->hasSearchInput()) {
            return view('satscribe', [
                'questionPlaceholder' => QuestionPlaceholder::rand(),
                'maxBitcoinBlockHeight' => $this->heightProvider->getMaxPossibleBlockHeight(),
            ]);
        }

        $search = $request->getSearchInput();
        $question = $request->getQuestionInput();
        $refresh = $request->isRefreshEnabled();

        try {
            $generatedPrompt = $action->execute($search, $refresh, $question);
        } catch (BlockchainException|OpenAIError $e) {
            Log::error('Failed to describe prompt result', [
                'search' => $search,
                'refresh' => $refresh,
                'question' => $question,
                'error' => $e->getMessage(),
            ]);
            return view('satscribe')
                ->withErrors(['search' => $e->getMessage()]);
        }

        return view('satscribe', [
            'result' => $generatedPrompt->result,
            'search' => $search,
            'question' => $question,
            'refreshed' => $refresh,
            'isFresh' => $generatedPrompt->isFresh,
            'questionPlaceholder' => QuestionPlaceholder::rand(),
            'maxBitcoinBlockHeight' => $this->heightProvider->getMaxPossibleBlockHeight(),
        ]);
    }

}
