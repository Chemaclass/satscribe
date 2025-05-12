<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Message;
use App\Repositories\ChatRepository;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;

final class HistoryController
{
    public function index(ChatRepository $repository): View
    {
        return view('history', [
            'chats' => $repository->getPagination(),
        ]);
    }

    public function getRaw(int $messageId): JsonResponse
    {
        $message = Message::find($messageId);

        return response()->json($message->meta['raw_data'] ?? null);
    }
}
