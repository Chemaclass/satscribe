<?php

namespace App\Http\Controllers;

use App\Actions\DescribePromptResultAction;
use App\Data\GeneratedPrompt;
use App\Data\Question;
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
            'descriptions' => PromptResult::latest()->simplePaginate(10),
        ]);
    }

    public function generate(Request $request, DescribePromptResultAction $action): View
    {
        $search = strtolower(trim($request->query('search')));
        $refresh = filter_var($request->query('refresh'), FILTER_VALIDATE_BOOL);
        $question = trim($request->query('question', ''));

        if ($search === '' || $search === '0') {
            return view('prompt-result.index', [
                'questionPlaceholder' => $this->questionPlaceholder(),
            ]);
        }

        try {
            $response = $action->execute($search, $refresh, $question);
        } catch (OpenAIError $e) {
            Log::error('Failed to describe prompt result', [
                'query' => $search,
                'refresh' => $refresh,
                'question' => $question,
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);

            return view('prompt-result.index')
                ->withErrors([
                    'search' => 'Oops! We couldn’t process your request. Try again later, or contact Chema for support.',
                ])
                ->withInput();
        }

        if (!$response instanceof GeneratedPrompt) {
            return view('prompt-result.index')
                ->withErrors(['search' => 'Could not fetch blockchain data.'])
                ->withInput();
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
