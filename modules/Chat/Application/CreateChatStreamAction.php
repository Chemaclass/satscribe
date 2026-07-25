<?php

declare(strict_types=1);

namespace Modules\Chat\Application;

use Generator;
use Modules\Blockchain\Domain\BlockchainFacadeInterface;
use Modules\Chat\Domain\CreateChatStreamActionInterface;
use Modules\Chat\Domain\Data\QuestionPlaceholder;
use Modules\Chat\Domain\Data\UserInputSanitizer;
use Modules\Chat\Domain\Repository\ChatRepositoryInterface;
use Modules\OpenAI\Application\OfflineFallback;
use Modules\OpenAI\Domain\Data\ModelSelection;
use Modules\OpenAI\Domain\Exception\OpenAIError;
use Modules\OpenAI\Domain\OpenAIFacadeInterface;
use Modules\Payment\Domain\PremiumAccessInterface;
use Modules\Shared\Domain\Chat\SentenceTrimmer;
use Modules\Shared\Domain\Data\Chat\PromptInput;
use Modules\Shared\Domain\Enum\Chat\PromptPersona;
use Psr\Log\LoggerInterface;

/**
 * @phpstan-import-type TStreamEvent from CreateChatStreamActionInterface
 */
final readonly class CreateChatStreamAction implements CreateChatStreamActionInterface
{
    public function __construct(
        private BlockchainFacadeInterface $blockchainFacade,
        private OpenAIFacadeInterface $openaiFacade,
        private ChatRepositoryInterface $repository,
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
    public function execute(
        PromptInput $input,
        PromptPersona $persona,
        string $question,
        bool $isPublic = false,
        ?ModelSelection $selection = null,
    ): Generator {
        $this->logger->debug('Create chat stream action started', [
            'input' => $input->text,
            'persona' => $persona->value,
        ]);

        // Before anything is spent: a model this deployment funds costs the
        // visitor a premium credit, and refusing here means no provider call
        // is made at all.
        $this->premiumAccess->authorise($selection);

        $this->rateLimiter->enforce();

        $blockchainData = $this->blockchainFacade->getBlockchainData($input);
        $cleanQuestion = $this->userInputSanitizer->sanitize($question);
        $additional = $this->contextBuilder->build($blockchainData, $input, $cleanQuestion);

        $fullResponse = '';

        try {
            $stream = $this->openaiFacade->generateTextStreaming(
                $blockchainData,
                $input,
                $persona,
                $cleanQuestion,
                null,
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

            $fullResponse = $this->offlineFallback->narrate($blockchainData, $persona);

            yield [
                'type' => 'chunk',
                'data' => $fullResponse,
            ];
        }

        // A provider can answer 200 and still yield nothing usable — a quota
        // notice in the body, or a delta shape this app does not parse. The
        // chat is only written after the stream, so without this the row was
        // saved with an empty answer and reported as a success.
        if (trim($fullResponse) === '') {
            $this->logger->error('Model produced an empty response', [
                'input' => $input->text,
                'persona' => $persona->value,
            ]);

            throw OpenAIError::emptyResponse();
        }

        $fullResponse = SentenceTrimmer::toLastFullSentence($fullResponse);

        $chat = $this->repository->createChat(
            $input,
            $fullResponse,
            $blockchainData->current(),
            $persona,
            $cleanQuestion,
            $isPublic,
        );

        $this->logger->info('Streaming chat created', ['chat_id' => $chat->id]);

        $suggestions = $input->isBlock()
            ? QuestionPlaceholder::forBlock()
            : QuestionPlaceholder::forTx();

        yield [
            'type' => 'done',
            'data' => [
                'chatUlid' => $chat->ulid,
                'content' => $fullResponse,
                'suggestions' => $suggestions,
                'maxBitcoinBlockHeight' => $this->blockchainFacade->getMaxPossibleBlockHeight(),
                'search' => $input->text,
            ],
        ];
    }

}
