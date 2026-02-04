<?php

declare(strict_types=1);

namespace Modules\Chat\Application;

use Generator;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Blockchain\Domain\BlockchainFacadeInterface;
use Modules\Chat\Domain\CreateChatStreamActionInterface;
use Modules\Chat\Domain\Data\QuestionPlaceholder;
use Modules\Chat\Domain\Data\UserInputSanitizer;
use Modules\Chat\Domain\Repository\ChatRepositoryInterface;
use Modules\OpenAI\Domain\OpenAIFacadeInterface;
use Modules\Shared\Domain\Data\Chat\PromptInput;
use Modules\Shared\Domain\Enum\Chat\PromptPersona;
use Psr\Log\LoggerInterface;

final readonly class CreateChatStreamAction implements CreateChatStreamActionInterface
{
    private const RATE_LIMIT_SECONDS = 86400;

    public function __construct(
        private BlockchainFacadeInterface $blockchainFacade,
        private OpenAIFacadeInterface $openaiFacade,
        private ChatRepositoryInterface $repository,
        private UserInputSanitizer $userInputSanitizer,
        private AdditionalContextBuilder $contextBuilder,
        private LoggerInterface $logger,
        private string $trackingId = '',
        private int $maxOpenAIAttempts = 1000,
    ) {
    }

    /**
     * @return Generator<array{type: string, data: mixed}>
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

        $this->enforceRateLimit();

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

        $fullResponse = $this->trimToLastFullSentence($fullResponse);

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

    private function enforceRateLimit(): void
    {
        $key = "openai:{$this->trackingId}";

        if (RateLimiter::tooManyAttempts($key, $this->maxOpenAIAttempts)) {
            throw new ThrottleRequestsException(
                "You have reached the daily OpenAI limit of {$this->maxOpenAIAttempts} requests.",
            );
        }

        RateLimiter::hit($key, self::RATE_LIMIT_SECONDS);
    }

    private function trimToLastFullSentence(string $text): string
    {
        $text = trim($text);

        preg_match_all('/[.?!]/u', $text, $matches, PREG_OFFSET_CAPTURE);

        if (isset($matches[0]) && $matches[0] !== []) {
            $last = end($matches[0]);
            $cutPos = $last[1] + mb_strlen($last[0]);
            $clean = mb_substr($text, 0, $cutPos);

            return trim((string) preg_replace('/(\*\*|\*|_|\-)+$/u', '', $clean));
        }

        return $text;
    }
}
