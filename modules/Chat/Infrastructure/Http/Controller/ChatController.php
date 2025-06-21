<?php

declare(strict_types=1);

namespace Modules\Chat\Infrastructure\Http\Controller;

use App\Exceptions\BlockchainException;
use App\Exceptions\OpenAIError;
use App\Http\Requests\HomeIndexRequest;
use App\Models\Chat;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Chat\Application\ChatService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class ChatController
{
    public function __construct(
        private ChatService $chatService,
        private LoggerInterface $logger,
    ) {
    }

    public function show(Chat $chat): View
    {
        return view('home', $this->chatService->getChatData($chat));
    }

    public function addMessage(Request $request, Chat $chat): JsonResponse
    {
        if (tracking_id() !== $chat->tracking_id) {
            abort(403, 'You are not allowed to send messages to this chat.');
        }

        return response()->json(
            $this->chatService->addMessage($chat, (string) $request->input('message'))
        );
    }

    public function index(): View
    {
        return view('home', $this->chatService->getIndexData());
    }

    public function createChat(HomeIndexRequest $request): JsonResponse
    {
        $this->logger->info('Creating chat request', [
            'search' => $request->hasSearchInput() ? $request->getSearchInput() : null,
            'persona' => $request->getPersonaInput(),
            'ip' => $request->ip(),
        ]);

        try {
            $data = $this->chatService->createChat($request);
            $this->logger->info('Chat created successfully', [
                'chat_ulid' => $data['chatUlid'] ?? null,
            ]);
        } catch (BlockchainException|OpenAIError $e) {
            $this->logger->error('Failed to describe prompt result', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'search' => $request->hasSearchInput() ? $request->getSearchInput() : null,
                'persona' => $request->getPersonaInput(),
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json($data);
    }
}
