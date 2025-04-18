<?php

namespace App\Http\Controllers;

use App\Actions\DescribePromptResultAction;
use App\Data\Question;
use App\Exceptions\BlockchainException;
use App\Exceptions\OpenAIError;
use App\Models\PromptResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

final class PromptResultController
{
    public function history(): View
    {
        return view('prompt-result/history', [
            'descriptions' => PromptResult::latest()->simplePaginate(5),
        ]);
    }

    public function generate(Request $request, DescribePromptResultAction $action): View
    {
        $validated = $request->validate([
            'search' => [
                'nullable', 'string', function ($attribute, $value, $fail) {
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
            return view('prompt-result.index', [
                'questionPlaceholder' => $this->questionPlaceholder(),
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

            $view = view('prompt-result.index')
                ->withErrors(['search' => $e->getMessage()]);

            if (app()->isLocal()) {
                $view->withInput();
            }

            return $view;
        }

        return view('prompt-result.index', [
            'result' => $response->result,
            'search' => $search,
            'question' => $question,
            'refreshed' => $refresh,
            'isFresh' => $response->isFresh,
            'questionPlaceholder' => $this->questionPlaceholder(),
        ]);
    }

    private function questionPlaceholder(): string
    {
        return Question::SAMPLE_QUESTIONS[array_rand(Question::SAMPLE_QUESTIONS)];
    }
}
