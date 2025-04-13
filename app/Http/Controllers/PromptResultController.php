<?php

namespace App\Http\Controllers;

use App\Actions\DescribePromptResultAction;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PromptResultController
{
    public function index(): View
    {
        return view('prompt-result.index');
    }

    public function describe(Request $request, DescribePromptResultAction $action): View
    {
        $input = strtolower(trim($request->query('input')));
        if (!$input) {
            return view('prompt-result.index');
        }

        $result = $action->execute($input);

        if (!$result) {
            return view('prompt-result.index')
                ->withErrors(['input' => 'Could not fetch blockchain data.']);
        }

        return view('prompt-result.index', [
            'result' => $result,
            'input' => $input,
        ]);
    }
}
