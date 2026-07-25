<?php

declare(strict_types=1);

namespace Modules\Chat\Infrastructure\Http\Controller;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Chat\Application\HistoryService;
use Modules\Chat\Domain\Exception\MessageNotFound;
use Modules\Chat\Domain\Exception\RawMessageNotVisible;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

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
        try {
            return response()->json($this->service->getRawMessageData($messageId, tracking_id()));
        } catch (MessageNotFound) {
            abort(Response::HTTP_NOT_FOUND, 'Message not found.');
        } catch (RawMessageNotVisible) {
            abort(Response::HTTP_FORBIDDEN, 'You are not allowed to view this message.');
        }
    }
}
