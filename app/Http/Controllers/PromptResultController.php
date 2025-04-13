<?php

namespace App\Http\Controllers;

use App\Data\BlockchainData;
use App\Repositories\PromptResultRepository;
use App\Services\BlockchainService;
use App\Services\OpenAIService;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PromptResultController
{
    public function __construct(
        private readonly PromptResultRepository $repository,
        private readonly BlockchainService $btc,
        private readonly OpenAIService $ai,
    ) {}

    public function index(): View
    {
        return view('prompt-result.index');
    }

    public function describe(Request $request): View
    {
        $input = strtolower(trim($request->query('input')));
        $type = is_numeric($input) ? 'block' : 'transaction';

        if (!$input) {
            return view('prompt-result.index');
        }

        // Check cached result
        $existing = $this->repository->findByTypeAndInput($type, $input);
        if ($existing) {
            return $this->renderResultView($existing->ai_response, $existing->raw_data, $input);
        }

        // Fetch blockchain data
        $data = $this->btc->getData($input);
        if (!$data) {
            return view('prompt-result.index')->withErrors(['input' => 'Could not fetch blockchain data.']);
        }

        // Generate AI response
        $text = $this->ai->generateText($data, $type);

        // Save to DB
        $this->repository->save($type, $input, $text, $data);

        return $this->renderResultView($text, $data->toArray(), $input);
    }

    private function renderResultView(string $text, array $data, string $input): View
    {
        return view('prompt-result.index', [
            'description' => $text,
            'data' => $data,
            'input' => $input,
        ]);
    }
}
