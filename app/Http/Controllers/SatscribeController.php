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
use function view;
use function view as view1;
use function view as view2;

final class SatscribeController
{
    public function index(SatscribeIndexRequest $request, SatscribeAction $action): View
    {
        $hasSearchInput = $request->has('search') && !empty($request->get('search'));
        if (!$hasSearchInput) {
            return view('satscribe.index', [
                'questionPlaceholder' => Question::rand(),
                'maxBitcoinBlockHeight' => $this->getMaxBitcoinBlockHeight(),
            ]);
        }

        $validated = $request->validated();
        $search = strtolower(trim($validated['search'] ?? ''));
        $question = trim($validated['question'] ?? '');

        $refresh = filter_var($request->query('refresh'), FILTER_VALIDATE_BOOL);

        try {
            $response = $action->execute($search, $refresh, $question);
        } catch (BlockchainException|OpenAIError $e) {
            Log::error('Failed to describe prompt result', [
                'search' => $search,
                'refresh' => $refresh,
                'question' => $question,
                'error' => $e->getMessage(),
            ]);
            return view2('satscribe.index')
                ->withErrors(['search' => $e->getMessage()]);
        }

        return view1('satscribe.index', [
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
