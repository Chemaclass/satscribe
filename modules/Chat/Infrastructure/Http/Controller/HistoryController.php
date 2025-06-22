<?php

declare(strict_types=1);

namespace Modules\Chat\Infrastructure\Http\Controller;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Chat\Application\HistoryService;
use Symfony\Component\HttpFoundation\JsonResponse;

final readonly class HistoryController
{
    public function __construct(
        private HistoryService $service,
    ) {
    }

    public function index(Request $request): View
    {
        $showAll = $request->boolean('all');
        $pagination = $this->service->getHistory($showAll);
        $pagination->appends($request->query());

        return view('history', [
            'chats' => $pagination,
        ]);
    }

    public function getRaw(int $messageId): JsonResponse
    {
        return response()->json($this->service->getRawMessageData($messageId));
    }
}
