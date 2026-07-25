<?php

declare(strict_types=1);

namespace Modules\Chat\Application;

use App\Models\Chat;
use App\Models\Message;
use Generator;
use Modules\Blockchain\Domain\BlockchainFacadeInterface;
use Modules\Chat\Domain\AddMessageStreamActionInterface;
use Modules\Chat\Domain\CreateChatStreamActionInterface;
use Modules\Chat\Domain\Data\QuestionPlaceholder;
use Modules\Chat\Domain\Data\UserInputSanitizer;
use Modules\Chat\Domain\Repository\ChatRepositoryInterface;
use Modules\Chat\Domain\Repository\MessageRepositoryInterface;
use Modules\OpenAI\Domain\Data\ModelSelection;
use Modules\OpenAI\Domain\Exception\OpenAIError;
use Modules\OpenAI\Application\OfflineFallback;
use Modules\OpenAI\Domain\OpenAIFacadeInterface;
use Modules\Payment\Domain\PremiumAccessInterface;
use Modules\Shared\Domain\Chat\SentenceTrimmer;
use Modules\Shared\Domain\Data\Chat\PromptInput;
use Modules\Shared\Domain\Enum\Chat\PromptPersona;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * @phpstan-import-type TStreamEvent from CreateChatStreamActionInterface
 * @phpstan-import-type TStreamDoneEvent from CreateChatStreamActionInterface
 */
final readonly class AddMessageStreamAction implements AddMessageStreamActionInterface
{
    public function __construct(
        private BlockchainFacadeInterface $blockchainFacade,
        private OpenAIFacadeInterface $openAIFacade,
        private ChatRepositoryInterface $chatRepository,
        private MessageRepositoryInterface $messageRepository,
        private UserInputSanitizer $userInputSanitizer,
        private AdditionalContextBuilder $contextBuilder,
        private OpenAiRateLimiter $rateLimiter,
        private PremiumAccessInterface $premiumAccess,
        private OfflineFallback $offlineFallback,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return Generator<TStreamEvent>
     */
    public function execute(Chat $chat, string $message, ?ModelSelection $selection = null): Generator
    {
        $this->logger->debug('Adding streaming message to chat', ['chat_id' => $chat->id]);
        // Before anything is spent: a model this deployment funds costs the
        // visitor a premium credit, and refusing here means no provider call
        // is made at all.
        $this->premiumAccess->authorise($selection);

        $this->rateLimiter->enforce();

        $firstUserMessage = $chat->getFirstUserMessage();
        $rawInput = $firstUserMessage->input;
        if ($rawInput === null) {
            throw new RuntimeException("Chat {$chat->id} has no input recorded on its first user message.");
        }

        $input = PromptInput::fromRaw($rawInput);
        $cleanMsg = $this->userInputSanitizer->sanitize($message);
        $persona = PromptPersona::tryFrom((string) $firstUserMessage->persona)
            ?? PromptPersona::from(PromptPersona::DEFAULT);

        // The message cache is keyed by input/persona/question, not by model, so
        // it can only be reused when the request took the default model.
        $cachedMessage = $selection instanceof ModelSelection
            ? null
            : $this->messageRepository->findAssistantMessage($input, $persona, $cleanMsg);

        if ($cachedMessage instanceof Message) {
            yield [
                'type' => 'chunk',
                'data' => $cachedMessage->content,
            ];

            yield $this->buildDoneEvent($chat, $input, $cachedMessage->content);

            return;
        }

        $data = $this->blockchainFacade->getBlockchainData($input);
        $additional = $this->contextBuilder->build($data, $input, $cleanMsg);

        $fullResponse = '';

        try {
            $stream = $this->openAIFacade->generateTextStreaming(
                $data,
                $input,
                $persona,
                $cleanMsg,
                $chat,
                $additional,
                $selection,
            );

            foreach ($stream as $chunk) {
                $fullResponse .= $chunk;

                yield [
                    'type' => 'chunk',
                    'data' => $chunk,
                ];
            }
        } catch (OpenAIError $e) {
            // Only when the provider failed before saying anything. Half an
            // answer followed by a templated summary would read as one voice.
            if ($fullResponse !== '' || !$this->offlineFallback->isEnabled()) {
                throw $e;
            }

            // Still an error in the log: the visitor gets something readable,
            // the operator still learns the provider is down.
            $this->logger->error('Falling back to the offline narrator', ['error' => $e->getMessage()]);

            $fullResponse = $this->offlineFallback->narrate($data, $persona);

            yield [
                'type' => 'chunk',
                'data' => $fullResponse,
            ];
        }

        // Same guard as chat creation: a provider that yields nothing usable
        // would otherwise persist a blank reply and report it as a success.
        if (trim($fullResponse) === '') {
            $this->logger->error('Model produced an empty response', ['chat_id' => $chat->id]);

            throw OpenAIError::emptyResponse();
        }

        $fullResponse = SentenceTrimmer::toLastFullSentence($fullResponse);

        $this->chatRepository->addMessageToChat($chat, $cleanMsg, $fullResponse);
        $this->logger->debug('Streaming message added to chat', ['chat_id' => $chat->id]);

        yield $this->buildDoneEvent($chat, $input, $fullResponse);
    }

    /**
     * @return TStreamDoneEvent
     */
    private function buildDoneEvent(Chat $chat, PromptInput $input, string $content): array
    {
        $suggestions = $input->isBlock()
            ? QuestionPlaceholder::forBlock()
            : QuestionPlaceholder::forTx();

        return [
            'type' => 'done',
            'data' => [
                'chatUlid' => $chat->ulid,
                'content' => $content,
                'suggestions' => $suggestions,
            ],
        ];
    }

}
