<?php

declare(strict_types=1);

namespace Modules\Chat\Application;

use App\Models\Chat;
use App\Models\Message;
use Modules\Blockchain\Domain\BlockchainFacadeInterface;
use Modules\Blockchain\Domain\Exception\BlockchainException;
use Modules\Chat\Domain\AddMessageActionInterface;
use Modules\Chat\Domain\CreateChatActionInterface;
use Modules\Chat\Domain\Data\QuestionPlaceholder;
use Modules\Chat\Domain\Repository\ChatRepositoryInterface;
use Modules\Chat\Infrastructure\Http\Request\CreateChatRequest;
use Modules\OpenAI\Domain\Exception\OpenAIError;
use Modules\Shared\Domain\Data\Chat\PromptInput;
use Modules\Shared\Domain\Enum\Chat\PromptPersona;

final readonly class ChatService
{
    public function __construct(
        private BlockchainFacadeInterface $blockchainFacade,
        private CreateChatActionInterface $createChatAction,
        private AddMessageActionInterface $addMessageAction,
        private ChatRepositoryInterface $chatRepository,
        private SuggestedPromptService $promptService,
    ) {
    }

    /**
     * Prepare data for showing a chat.
     *
     * @return array<string, mixed>
     */
    public function getChatData(Chat $chat): array
    {
        $chat->load('messages');

        /** @var Message $firstMsg */
        $firstMsg = $chat->messages()->first();

        return [
            'questionPlaceholder' => QuestionPlaceholder::rand(),
            'suggestedPromptsGrouped' => $this->promptService->getGroupedPrompts($chat),
            'suggestions' => $firstMsg->isBlock() ? QuestionPlaceholder::forBlock() : QuestionPlaceholder::forTx(),
            'maxBitcoinBlockHeight' => $this->blockchainFacade->getMaxPossibleBlockHeight(),
            'latestBlockHeight' => $this->blockchainFacade->getCurrentBlockHeight(),
            'personaDescriptions' => PromptPersona::descriptions()->toJson(),
            'question' => $chat->messages()->first()->content,
            'chat' => $chat,
            'search' => $firstMsg->meta['input'] ?? '',
            'persona' => $firstMsg->meta['persona'] ?? '',
            'totalChats' => $this->chatRepository->getTotalChats(),
        ];
    }

    /**
     * Add a new message to the chat and return response data.
     *
     * @return array<string, mixed>
     */
    public function addMessage(Chat $chat, string $message): array
    {
        $this->addMessageAction->execute($chat, $message);

        return [
            'content' => $chat->getLastAssistantMessage()->content,
            'suggestions' => $chat->isBlock() ? QuestionPlaceholder::forBlock() : QuestionPlaceholder::forTx(),
        ];
    }

    /**
     * Data required for the home page view.
     *
     * @return array<string, mixed>
     */
    public function getIndexData(): array
    {
        return [
            'questionPlaceholder' => QuestionPlaceholder::rand(),
            'suggestedPromptsGrouped' => $this->promptService->getGroupedPrompts(),
            'maxBitcoinBlockHeight' => $this->blockchainFacade->getMaxPossibleBlockHeight(),
            'latestBlockHeight' => $this->blockchainFacade->getCurrentBlockHeight(),
            'personaDescriptions' => PromptPersona::descriptions()->toJson(),
            'totalMessages' => Message::count(),
        ];
    }

    /**
     * Handle chat creation request and return data for JSON response.
     *
     * @throws BlockchainException|OpenAIError
     *
     * @return array<string, mixed>
     */
    public function createChat(CreateChatRequest $request): array
    {
        $search = $this->getPromptInput($request);
        $persona = $this->getPromptPersona($request);
        $question = $request->getQuestionInput();
        $refreshEnabled = $request->isRefreshEnabled();
        $isPublic = !$request->isPrivate();

        $actionResult = $this->createChatAction->execute(
            $search,
            $persona,
            $question,
            $refreshEnabled,
            $isPublic,
        );

        return [
            'maxBitcoinBlockHeight' => $this->blockchainFacade->getMaxPossibleBlockHeight(),
            'latestBlockHeight' => $this->blockchainFacade->getCurrentBlockHeight(),
            'search' => $search,
            'chatUlid' => $actionResult->chat->ulid,
            'content' => $actionResult->chat->getLastAssistantMessage()->content,
            'html' => view('partials.chat-creation', [
                'chat' => $actionResult->chat,
                'suggestions' => $this->generateSuggestions($search),
            ])->render(),
        ];
    }

    private function getPromptInput(CreateChatRequest $request): PromptInput
    {
        if ($request->hasSearchInput()) {
            return PromptInput::fromRaw($request->getSearchInput());
        }

        return PromptInput::fromRaw($this->blockchainFacade->getCurrentBlockHeight());
    }

    private function getPromptPersona(CreateChatRequest $request): PromptPersona
    {
        return PromptPersona::tryFrom($request->getPersonaInput())
            ?? PromptPersona::from(PromptPersona::DEFAULT);
    }

    private function generateSuggestions(PromptInput $search): array
    {
        return $search->isBlock()
            ? QuestionPlaceholder::forBlock()
            : QuestionPlaceholder::forTx();
    }
}
