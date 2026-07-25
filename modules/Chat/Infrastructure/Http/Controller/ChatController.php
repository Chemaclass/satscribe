<?php

declare(strict_types=1);

namespace Modules\Chat\Infrastructure\Http\Controller;

use App\Models\Chat;
use Generator;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Blockchain\Domain\BlockchainFacadeInterface;
use Modules\Blockchain\Domain\Exception\BlockchainException;
use Modules\Chat\Application\ChatService;
use Modules\Chat\Domain\AddMessageStreamActionInterface;
use Modules\Chat\Domain\CreateChatStreamActionInterface;
use Modules\Chat\Infrastructure\Http\Request\CreateChatRequest;
use Modules\OpenAI\Domain\Exception\OpenAIError;
use Modules\Shared\Domain\Data\Chat\PromptInput;
use Modules\Shared\Domain\Enum\Chat\PromptPersona;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class ChatController
{
    public function __construct(
        private ChatService $chatService,
        private BlockchainFacadeInterface $blockchainFacade,
        private CreateChatStreamActionInterface $createChatStreamAction,
        private AddMessageStreamActionInterface $addMessageStreamAction,
        private LoggerInterface $logger,
    ) {
    }

    public function show(Chat $chat): View
    {
        if (!$chat->canShow(tracking_id())) {
            abort(Response::HTTP_FORBIDDEN, 'You are not allowed to view this chat.');
        }

        return view('home', $this->chatService->getChatData($chat));
    }

    public function addMessage(Request $request, Chat $chat): JsonResponse
    {
        $this->abortUnlessOwner($chat, 'You are not allowed to send messages to this chat.');

        return response()->json(
            $this->chatService->addMessage($chat, (string) $request->input('message')),
        );
    }

    public function addMessageStream(Request $request, Chat $chat): StreamedResponse
    {
        $this->abortUnlessOwner($chat, 'You are not allowed to send messages to this chat.');

        $message = (string) $request->input('message');

        return $this->streamEvents(
            fn (): Generator => $this->addMessageStreamAction->execute($chat, $message),
            'Streaming message failed',
        );
    }

    public function index(): View
    {
        return view('home', $this->chatService->getIndexData());
    }

    public function createChat(CreateChatRequest $request): JsonResponse
    {
        $this->logger->debug('Creating chat request', [
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

    public function createChatStream(CreateChatRequest $request): StreamedResponse
    {
        $this->logger->debug('Creating streaming chat request', [
            'search' => $request->hasSearchInput() ? $request->getSearchInput() : null,
            'persona' => $request->getPersonaInput(),
            'ip' => $request->ip(),
        ]);

        $search = $request->hasSearchInput()
            ? PromptInput::fromRaw($request->getSearchInput())
            : PromptInput::fromRaw($this->blockchainFacade->getCurrentBlockHeight());

        $persona = PromptPersona::tryFrom($request->getPersonaInput())
            ?? PromptPersona::from(PromptPersona::DEFAULT);

        $question = $request->getQuestionInput();
        $isPublic = !$request->isPrivate();

        return $this->streamEvents(
            fn (): Generator => $this->createChatStreamAction->execute($search, $persona, $question, $isPublic),
            'Streaming chat failed',
        );
    }

    public function share(Chat $chat, Request $request): JsonResponse
    {
        $this->abortUnlessOwner($chat, 'You are not allowed to share this chat.');

        $chat->is_shared = (bool)$request->input('shared');
        $chat->save();

        return response()->json(['shared' => true]);
    }

    public function toggleVisibility(Chat $chat): JsonResponse
    {
        $this->abortUnlessOwner($chat, 'You are not allowed to change this chat visibility.');

        $chat->is_public = !$chat->is_public;
        $chat->save();

        return response()->json(['is_public' => $chat->is_public]);
    }

    private function abortUnlessOwner(Chat $chat, string $reason): void
    {
        if (tracking_id() !== $chat->tracking_id) {
            abort(Response::HTTP_FORBIDDEN, $reason);
        }
    }

    /**
     * Wraps an action's event stream in the SSE wire format: the `data:` framing,
     * the flush-per-event discipline and the no-buffering headers are one protocol
     * contract, so both streaming endpoints share a single implementation.
     *
     * @param callable(): Generator<mixed> $events
     */
    private function streamEvents(callable $events, string $errorLogMessage): StreamedResponse
    {
        return new StreamedResponse(function () use ($events, $errorLogMessage): void {
            try {
                foreach ($events() as $event) {
                    $this->emit($event);
                }
            } catch (BlockchainException|OpenAIError $e) {
                $this->logger->error($errorLogMessage, ['error' => $e->getMessage()]);
                $this->emit(['type' => 'error', 'data' => $e->getMessage()]);
            }
        }, Response::HTTP_OK, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function emit(mixed $event): void
    {
        echo 'data: ' . json_encode($event, JSON_THROW_ON_ERROR) . "\n\n";
        ob_flush();
        flush();
    }
}
