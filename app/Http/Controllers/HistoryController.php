<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\SatscribeDescription;
use App\Repositories\ConversationRepository;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;

final class HistoryController
{
    public function index(ConversationRepository $repository): View
    {
        return view('history', [
            'descriptions' => $repository->getPagination(),
        ]);
    }

    public function getRaw(int $id): JsonResponse
    {
        $result = SatscribeDescription::findOrFail($id);

        return response()->json($result->raw_data);
    }
}
