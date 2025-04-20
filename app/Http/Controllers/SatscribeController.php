<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\SatscribeAction;
use App\Data\Question;
use App\Exceptions\BlockchainException;
use App\Exceptions\OpenAIError;
use App\Http\Requests\SatscribeIndexRequest;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

final class SatscribeController
{
    public function index(SatscribeIndexRequest $request, SatscribeAction $action): View
    {
        if (!$request->hasSearchInput()) {
            return view('satscribe', [
                'questionPlaceholder' => Question::rand(),
                'maxBitcoinBlockHeight' => $this->getMaxBitcoinBlockHeight(),
            ]);
        }

        $search = $request->getSearchInput();
        $question = $request->getQuestionInput();
        $refresh = $request->isRefreshEnabled();

        try {
            $response = $action->execute($search, $refresh, $question);
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
            'result' => $response->result,
            'search' => $search,
            'question' => $question,
            'refreshed' => $refresh,
            'isFresh' => $response->isFresh,
            'questionPlaceholder' => Question::rand(),
            'maxBitcoinBlockHeight' => $this->getMaxBitcoinBlockHeight(),
        ]);
    }

    /**
     * @todo extract service
     */
    private function getMaxBitcoinBlockHeight(): int
    {
        $genesisTimestamp = (new DateTimeImmutable('2009-01-03 19:15:05', new DateTimeZone('UTC')))->getTimestamp();
        $currentTimestamp = now()->setTimezone('UTC')->getTimestamp();

        $elapsedSeconds = $currentTimestamp - $genesisTimestamp;
        $estimatedHeight = (int) floor($elapsedSeconds / 600);
        $buffer = (int) ceil($estimatedHeight * 0.06);

        return $estimatedHeight + $buffer;
    }
}
