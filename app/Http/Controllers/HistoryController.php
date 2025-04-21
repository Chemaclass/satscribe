<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PromptResult;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;

final class HistoryController
{
    public function index(): View
    {
        $perPage = config('app.pagination.per_page');

        $promptResults = PromptResult::latest()->simplePaginate($perPage);

        return view('history', [
            'promptResults' => $promptResults,
        ]);
    }

    public function getRaw(int $id): JsonResponse
    {
        $result = PromptResult::findOrFail($id);

        return response()->json($result->raw_data);
    }
}
