<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Message;
use App\Repositories\ChatRepository;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;

final class HistoryController
{
    public function index(Request $request, ChatRepository $repository): View
    {
        $showAll = $request->boolean('all');
        $pagination = $repository->getPagination($showAll);
        $pagination->appends($request->query());

        return view('history', [
            'chats' => $pagination,
        ]);
    }

    public function getRaw(int $messageId): JsonResponse
    {
        $message = Message::find($messageId);

        return response()->json($message->meta['raw_data'] ?? null);
    }
}
