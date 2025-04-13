<?php
namespace App\Http\Controllers;

use App\Models\PromptResult;
use App\Services\BlockchainService;
use App\Services\OpenAIService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PromptResultController extends Controller
{
    public function index(): View
    {
        return view('describe');
    }

    public function describe(Request $request, BlockchainService $btc, OpenAIService $ai): RedirectResponse|View
    {
        $input = strtolower(trim($request->input('input')));
        $type = is_numeric($input) ? 'block' : 'transaction';

        // Check if an existing AI result already exists
        $existing = PromptResult::where('type', $type)->where('input', $input)->first();
        if ($existing !== null) {
            return $this->renderResultView($existing->generated_text, $existing->raw_data);
        }

        // Fetch blockchain data
        $blockchainData = $btc->getData($input);
        if (empty($blockchainData)) {
            return back()->withErrors(['input' => 'Could not fetch blockchain data.']);
        }

        // Generate the AI text
        $generatedText = $ai->generateDescription($blockchainData, $type);

        // Save to database
        $result = PromptResult::create([
            'type' => $type,
            'input' => $input,
            'generated_text' => $generatedText,
            'raw_data' => $blockchainData,
        ]);

        return $this->renderResultView($result->generated_text, $result->raw_data);
    }

    private function renderResultView(string $text, array $data): View
    {
        return view('describe', [
            'description' => $text,
            'data' => $data,
        ]);
    }
}
