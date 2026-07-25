<?php

declare(strict_types=1);

namespace Modules\Chat\Application;

use Generator;
use Modules\Blockchain\Domain\BlockchainFacadeInterface;
use Modules\Chat\Domain\CreateChatStreamActionInterface;
use Modules\Chat\Domain\Data\QuestionPlaceholder;
use Modules\Chat\Domain\Data\UserInputSanitizer;
use Modules\Chat\Domain\Repository\ChatRepositoryInterface;
use Modules\OpenAI\Domain\OpenAIFacadeInterface;
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
    ): Generator {
        $this->logger->debug('Create chat stream action started', [
            'input' => $input->text,
            'persona' => $persona->value,
        ]);

        $this->rateLimiter->enforce();

        $blockchainData = $this->blockchainFacade->getBlockchainData($input);
        $cleanQuestion = $this->userInputSanitizer->sanitize($question);
        $additional = $this->contextBuilder->build($blockchainData, $input, $cleanQuestion);

        $fullResponse = '';

        foreach ($this->openaiFacade->generateTextStreaming($blockchainData, $input, $persona, $cleanQuestion, null, $additional) as $chunk) {
            $fullResponse .= $chunk;

            yield [
                'type' => 'chunk',
                'data' => $chunk,
            ];
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
