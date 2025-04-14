<?php

namespace App\Http\Controllers;

use App\Actions\DescribePromptResultAction;
use App\Data\DescribedPrompt;
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

    public function describe(Request $request, DescribePromptResultAction $action): View
    {
        $q = strtolower(trim($request->query('q')));
        $refresh = filter_var($request->query('refresh'), FILTER_VALIDATE_BOOL);

        if ($q === '' || $q === '0') {
            return view('prompt-result.index');
        }

        try {
            $response = $action->execute($q, $refresh);
        } catch (OpenAIError $e) {
            Log::error($e->getMessage());
            return view('prompt-result.index')
                ->withErrors(['q' => 'Ups, something went wrong. Tell Chema, please!.']);
        }

        if (!$response instanceof DescribedPrompt) {
            return view('prompt-result.index')
                ->withErrors(['q' => 'Could not fetch blockchain data.']);
        }

        return view('prompt-result.index', [
            'result' => $response->result,
            'q' => $q,
            'refreshed' => $refresh,
            'isFresh' => $response->isFresh,
        ]);
    }
}
