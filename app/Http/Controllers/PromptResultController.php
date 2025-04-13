<?php

namespace App\Http\Controllers;

use App\Models\PromptResult;
use App\Services\BlockchainService;
use App\Services\OpenAIService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PromptResultController
{
    public function index(): View
    {
        return view('promptResult.index');
    }
    public function describe(Request $request, BlockchainService $btc, OpenAIService $ai): View
    {
        $input = strtolower(trim($request->query('input')));
        $type = is_numeric($input) ? 'block' : 'transaction';

        if (!$input) {
            return view('promptResult.index'); // Just render empty form
        }

        // Try cache first
        $existing = PromptResult::where('type', $type)->where('input', $input)->first();
        if ($existing) {
            return $this->renderResultView($existing->ai_response, $existing->raw_data, $input);
        }

        // Fetch blockchain data
        $data = $btc->getData($input);
        if (empty($data)) {
            return view('promptResult.index')->withErrors(['input' => 'Could not fetch blockchain data.']);
        }

        // Generate response
        $text = $ai->generateDescription($data, $type);

        // Save
        PromptResult::create([
            'type' => $type,
            'input' => $input,
            'ai_response' => $text,
            'raw_data' => $data,
        ]);

        return $this->renderResultView($text, $data, $input);
    }

    private function renderResultView(string $text, array $data, string $input): View
    {
        return view('promptResult.index', [
            'description' => $text,
            'data' => $data,
            'input' => $input,
        ]);
    }
}
