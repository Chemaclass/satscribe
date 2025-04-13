<?php

namespace App\Http\Controllers;

use App\Models\PromptResult;
use App\Actions\DescribePromptResultAction;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PromptResultController
{
    public function describe(Request $request, DescribePromptResultAction $action): View
    {
        $input = strtolower(trim($request->query('input')));
        $refresh = filter_var($request->query('refresh'), FILTER_VALIDATE_BOOL);

        if ($input === '' || $input === '0') {
            return view('prompt-result.index');
        }

        $result = $action->execute($input, $refresh);

        if (!$result instanceof PromptResult) {
            return view('prompt-result.index')
                ->withErrors(['input' => 'Could not fetch blockchain data.']);
        }

        return view('prompt-result.index', [
            'result' => $result,
            'input' => $input,
            'refreshed' => $refresh,
        ]);
    }
}
