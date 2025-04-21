<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PromptResult;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;

final class HistoryController
{
    // todo: create a new endpoint to retrieve the "show more" content and enable fast first rendering
    public function index(): View
    {
        return view('history', [
            'descriptions' => PromptResult::latest()->simplePaginate(3),
        ]);
    }

    public function getRaw(int $id): JsonResponse
    {
        $result = PromptResult::findOrFail($id);

        return response()->json($result->raw_data);
    }
}
