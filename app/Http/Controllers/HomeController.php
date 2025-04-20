<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\SatscribeAction;
use App\Data\Question;
use App\Exceptions\BlockchainException;
use App\Exceptions\OpenAIError;
use App\Models\PromptResult;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

final class HomeController
{
    public function __invoke(Request $request, SatscribeAction $action): View
    {
        if (!$this->hasSearchInput($request)) {
            return $this->renderInitialPromptView();
        }

        $validated = $this->validateRequest($request);
        $search = strtolower(trim($validated['search'] ?? ''));
        $question = trim($validated['question'] ?? '');

        $refresh = $this->shouldRefresh($request);

        try {
            $response = $action->execute($search, $refresh, $question);
        } catch (BlockchainException|OpenAIError $e) {
            $this->logPromptError($e, $search, $refresh, $question);
            return $this->renderErrorView($e);
        }

        return $this->renderResultView($response->result, $search, $question, $refresh, $response->isFresh);
    }

    private function validateRequest(Request $request): array
    {
        return $request->validate([
            'search' => [
                'nullable', 'string', function ($attribute, $value, $fail): void {
                    if (!preg_match('/^[a-f0-9]{64}$/i', $value) && !ctype_digit($value)) {
                        $fail('The '.$attribute.' must be a valid Bitcoin TXID or block height.');
                    }
                }
            ],
            'question' => ['nullable', 'string', 'max:200'],
        ]);
    }

    private function shouldRefresh(Request $request): bool
    {
        return filter_var($request->query('refresh'), FILTER_VALIDATE_BOOL);
    }

    private function hasSearchInput(Request $request): bool
    {
        return $request->has('search')
            && !empty($request->get('search'));
    }

    private function renderInitialPromptView(): View
    {
        return view('satscribe.index', [
            'questionPlaceholder' => Question::rand(),
            'maxBitcoinBlockHeight' => $this->getMaxBitcoinBlockHeight(),
        ]);
    }

    private function renderResultView(
        PromptResult $result,
        string $search,
        string $question,
        bool $refresh,
        bool $isFresh
    ): View {
        return view('satscribe.index', [
            'result' => $result,
            'search' => $search,
            'question' => $question,
            'refreshed' => $refresh,
            'isFresh' => $isFresh,
            'questionPlaceholder' => Question::rand(),
            'maxBitcoinBlockHeight' => $this->getMaxBitcoinBlockHeight(),
        ]);
    }

    private function renderErrorView(Throwable $e): View
    {
        return view('satscribe.index')
            ->withErrors(['search' => $e->getMessage()]);
    }

    private function logPromptError(Throwable $e, string $search, bool $refresh, string $question): void
    {
        Log::error('Failed to describe prompt result', [
            'search' => $search,
            'refresh' => $refresh,
            'question' => $question,
            'error' => $e->getMessage(),
        ]);
    }

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
