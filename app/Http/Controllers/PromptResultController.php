<?php

namespace App\Http\Controllers;

use App\Actions\DescribePromptResultAction;
use App\Data\Question;
use App\Exceptions\BlockchainException;
use App\Exceptions\OpenAIError;
use App\Models\PromptResult;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

final class PromptResultController extends AbstractController
{
    public function history(): View
    {
        return $this->render('prompt-result/history', [
            'descriptions' => PromptResult::latest()->simplePaginate(5),
        ]);
    }

    public function generate(Request $request, DescribePromptResultAction $action): View
    {
        $validated = $request->validate([
            'search' => [
                'nullable', 'string', function ($attribute, $value, $fail): void {
                    if (!preg_match('/^[a-f0-9]{64}$/i', $value) && !ctype_digit($value)) {
                        $fail('The '.$attribute.' must be a valid Bitcoin TXID or block height.');
                    }
                }
            ],
            'question' => ['nullable', 'string', 'max:200'],
        ]);

        $search = strtolower(trim($validated['search'] ?? ''));
        $question = trim($validated['question'] ?? '');
        $refresh = filter_var($request->query('refresh'), FILTER_VALIDATE_BOOL);

        if (!$request->has('search') || empty($request->get('search'))) {
            return $this->render('prompt-result.index', [
                'questionPlaceholder' => $this->questionPlaceholder(),
                'maxBitcoinBlockHeight' => $this->getMaxBitcoinBlockHeight(),
            ]);
        }

        try {
            $response = $action->execute($search, $refresh, $question);
        } catch (BlockchainException|OpenAIError $e) {
            Log::error('Failed to describe prompt result', [
                'search' => $search,
                'refresh' => $refresh,
                'question' => $question,
                'error' => $e->getMessage(),
            ]);

            return $this->render('prompt-result.index')
                ->withErrors(['search' => $e->getMessage()]);
        }

        return $this->render('prompt-result.index', [
            'result' => $response->result,
            'search' => $search,
            'question' => $question,
            'refreshed' => $refresh,
            'isFresh' => $response->isFresh,
            'questionPlaceholder' => $this->questionPlaceholder(),
            'maxBitcoinBlockHeight' => $this->getMaxBitcoinBlockHeight(),
        ]);
    }

    private function questionPlaceholder(): string
    {
        return Question::SAMPLE_QUESTIONS[array_rand(Question::SAMPLE_QUESTIONS)];
    }

    private function getMaxBitcoinBlockHeight(): int
    {
        $genesisTimestamp = (new DateTimeImmutable('2009-01-03 19:15:05', new DateTimeZone('UTC')))->getTimestamp();
        $currentTimestamp = now()->setTimezone('UTC')->getTimestamp();

        $elapsedSeconds = $currentTimestamp - $genesisTimestamp;
        $estimatedHeight = (int) floor($elapsedSeconds / 600); // 600 seconds = 10 minutes per block

        /**
         * Add a buffer (~6%) to account for future blocks beyond the estimated height.
         * This helps prevent edge cases where a valid height might be slightly ahead
         * of the computed value due to network variability or caching delays.
         */
        $buffer = (int) ceil($estimatedHeight * 0.06);

        return $estimatedHeight + $buffer;
    }
}
