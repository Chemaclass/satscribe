<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\SatscribeDescription;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;

final class HistoryController
{
    public function index(): View
    {
        $perPage = config('app.pagination.per_page');

        $promptResults = SatscribeDescription::latest()->simplePaginate($perPage);

        return view('history', [
            'promptResults' => $promptResults,
        ]);
    }

    public function getRaw(int $id): JsonResponse
    {
        $result = SatscribeDescription::findOrFail($id);

        return response()->json($result->raw_data);
    }
}
