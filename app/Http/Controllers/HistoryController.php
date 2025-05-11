<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Repositories\ConversationRepository;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;

final class HistoryController
{
    public function index(ConversationRepository $repository): View
    {
        return view('history', [
            'conversations' => $repository->getPagination(),
        ]);
    }

    public function getRaw(int $id): JsonResponse
    {
        /** @var Conversation $conversation */
        $conversation = Conversation::findOrFail($id);

        $rawData = $conversation->messages
            ->firstWhere('role', 'assistant')
            ->meta['raw_data'];

        return response()->json($rawData);
    }
}
