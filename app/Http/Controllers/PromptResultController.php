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
        $q = strtolower(trim($request->query('q')));
        $refresh = filter_var($request->query('refresh'), FILTER_VALIDATE_BOOL);
        $question = trim($request->query('question', ''));

        if ($q === '' || $q === '0') {
            return view('prompt-result.index', [
                'questionPlaceholder' => $this->questionPlaceholder(),
            ]);
        }

        try {
            $response = $action->execute($q, $refresh, $question);
        } catch (OpenAIError $e) {
            Log::error('Failed to describe prompt result', [
                'query' => $q,
                'refresh' => $refresh,
                'question' => $question,
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);

            return view('prompt-result.index')
                ->withErrors([
                    'q' => 'Oops! We couldn’t process your request. Try again later, or contact Chema for support.',
                ])
                ->withInput();
        }

        if (!$response instanceof GeneratedPrompt) {
            return view('prompt-result.index')
                ->withErrors(['q' => 'Could not fetch blockchain data.'])
                ->withInput();
        }

        return view('prompt-result.index', [
            'result' => $response->result,
            'q' => $q,
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
