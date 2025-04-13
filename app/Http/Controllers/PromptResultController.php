<?php

namespace App\Http\Controllers;

use App\Actions\DescribePromptResultAction;
use App\Data\DescribedPrompt;
use App\Models\PromptResult;
use Illuminate\Http\Request;
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
        $input = strtolower(trim($request->query('input')));
        $refresh = filter_var($request->query('refresh'), FILTER_VALIDATE_BOOL);

        if ($input === '' || $input === '0') {
            return view('prompt-result.index');
        }

        $response = $action->execute($input, $refresh);

        if (!$response instanceof DescribedPrompt) {
            return view('prompt-result.index')
                ->withErrors(['input' => 'Could not fetch blockchain data.']);
        }

        return view('prompt-result.index', [
            'result' => $response->result,
            'input' => $input,
            'refreshed' => $refresh,
            'isFresh' => $response->isFresh,
        ]);
    }
}
