<?php

declare(strict_types=1);

namespace Modules\Chat\Application;

use App\Models\Chat;
use App\Models\Message;
use Generator;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Blockchain\Domain\BlockchainFacadeInterface;
use Modules\Chat\Domain\AddMessageStreamActionInterface;
use Modules\Chat\Domain\Data\QuestionPlaceholder;
use Modules\Chat\Domain\Data\UserInputSanitizer;
use Modules\Chat\Domain\Repository\ChatRepositoryInterface;
use Modules\Chat\Domain\Repository\MessageRepositoryInterface;
use Modules\OpenAI\Domain\OpenAIFacadeInterface;
use Modules\Shared\Domain\Data\Chat\PromptInput;
use Modules\Shared\Domain\Enum\Chat\PromptPersona;
use Psr\Log\LoggerInterface;

final readonly class AddMessageStreamAction implements AddMessageStreamActionInterface
{
    private const RATE_LIMIT_SECONDS = 86400;

    public function __construct(
        private BlockchainFacadeInterface $blockchainFacade,
        private OpenAIFacadeInterface $openAIFacade,
        private ChatRepositoryInterface $chatRepository,
        private MessageRepositoryInterface $messageRepository,
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
    public function execute(Chat $chat, string $message): Generator
    {
        $this->logger->debug('Adding streaming message to chat', ['chat_id' => $chat->id]);
        $this->enforceRateLimit();

        $firstUserMessage = $chat->getFirstUserMessage();
        $input = PromptInput::fromRaw($firstUserMessage->input);
        $cleanMsg = $this->userInputSanitizer->sanitize($message);
        $persona = PromptPersona::from($firstUserMessage->persona);

        // Check for cached response first
        $cachedMessage = $this->messageRepository->findAssistantMessage($input, $persona, $cleanMsg);
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

        foreach ($this->openAIFacade->generateTextStreaming($data, $input, $persona, $cleanMsg, $chat, $additional) as $chunk) {
            $fullResponse .= $chunk;

            yield [
                'type' => 'chunk',
                'data' => $chunk,
            ];
        }

        $fullResponse = $this->trimToLastFullSentence($fullResponse);

        $this->chatRepository->addMessageToChat($chat, $cleanMsg, $fullResponse);
        $this->logger->debug('Streaming message added to chat', ['chat_id' => $chat->id]);

        yield $this->buildDoneEvent($chat, $input, $fullResponse);
    }

    /**
     * @return array{type: string, data: array<string, mixed>}
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
