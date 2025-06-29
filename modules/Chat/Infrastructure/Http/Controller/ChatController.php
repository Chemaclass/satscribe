<?php

declare(strict_types=1);

namespace Modules\Chat\Infrastructure\Http\Controller;

use App\Models\Chat;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Blockchain\Domain\Exception\BlockchainException;
use Modules\Chat\Application\ChatService;
use Modules\Chat\Infrastructure\Http\Request\CreateChatRequest;
use Modules\OpenAI\Domain\Exception\OpenAIError;
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
        if ($chat->canShow(tracking_id())) {
            abort(Response::HTTP_FORBIDDEN, 'You are not allowed to view this chat.');
        }

        return view('home', $this->chatService->getChatData($chat));
    }

    public function addMessage(Request $request, Chat $chat): JsonResponse
    {
        if (tracking_id() !== $chat->tracking_id) {
            abort(Response::HTTP_FORBIDDEN, 'You are not allowed to send messages to this chat.');
        }

        return response()->json(
            $this->chatService->addMessage($chat, (string) $request->input('message')),
        );
    }

    public function index(): View
    {
        return view('home', $this->chatService->getIndexData());
    }

    public function createChat(CreateChatRequest $request): JsonResponse
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

    public function share(Chat $chat): JsonResponse
    {
        if (tracking_id() !== $chat->tracking_id) {
            abort(Response::HTTP_FORBIDDEN, 'You are not allowed to share this chat.');
        }

        $chat->is_shared = true;
        $chat->save();

        return response()->json(['shared' => true]);
    }

    public function toggleVisibility(Chat $chat): JsonResponse
    {
        if (tracking_id() !== $chat->tracking_id) {
            abort(Response::HTTP_FORBIDDEN, 'You are not allowed to change this chat visibility.');
        }

        $chat->is_public = !$chat->is_public;
        $chat->save();

        return response()->json(['is_public' => $chat->is_public]);
    }
}
