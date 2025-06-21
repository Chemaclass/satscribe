<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;

final readonly class ChatController
{
    public function __construct(
        private ChatService $service,
    ) {
    }

    public function show(Chat $chat): View
    {
        return view('home', $this->service->getChatData($chat));
    }

    public function addMessage(Request $request, Chat $chat): JsonResponse
    {
        if (tracking_id() !== $chat->tracking_id) {
            abort(403, 'You are not allowed to send messages to this chat.');
        }

        return response()->json(
            $this->service->addMessage($chat, (string) $request->input('message'))
        );
    }
}
