<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Blockchain\BlockchainData;
use App\Data\CreateChatActionResult;
use App\Data\PromptInput;
use App\Enums\PromptPersona;
use App\Models\Chat;
use App\Repositories\ChatRepositoryInterface;
use App\Repositories\MessageRepositoryInterface;
use App\Services\BlockchainServiceInterface;
use App\Services\OpenAIService;
use App\Services\UserInputSanitizer;
use App\Services\AdditionalContextBuilder;
use App\Services\TransactionBacktraceService;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\RateLimiter;
use Psr\Log\LoggerInterface;

final readonly class CreateChatAction
{
    private const RATE_LIMIT_SECONDS = 86400; // 24 hours

    public function __construct(
        private BlockchainServiceInterface $blockchain,
        private OpenAIService $openai,
        private ChatRepositoryInterface $repository,
        private MessageRepositoryInterface $messageRepository,
        private UserInputSanitizer $userInputSanitizer,
        private AdditionalContextBuilder $contextBuilder,
        private TransactionBacktraceService $backtrace,
        private LoggerInterface $logger,
        private string $trackingId,
        private int $maxOpenAIAttempts,
    ) {
    }

    public function execute(
        PromptInput $input,
        PromptPersona $persona,
        string $question,
        bool $refreshEnabled = false,
        bool $isPrivate = false,
    ): CreateChatActionResult {
        $this->logger->info('Create chat action started', [
            'input' => $input->text,
            'persona' => $persona->value,
            'refresh' => $refreshEnabled,
        ]);
        // Return a cached result unless forced to refresh
        if (!$refreshEnabled) {
            $chat = $this->repository->findByCriteria($input, $persona, $question);

            if ($chat instanceof Chat && !$chat->force_refresh) {
                $this->logger->info('Returning cached chat', ['chat_id' => $chat->id]);
                return new CreateChatActionResult($chat, isFresh: false);
            }
        }

        $result = $this->createNewChat($input, $persona, $question, $refreshEnabled, $isPrivate);
        $this->logger->info('New chat created', ['chat_id' => $result->id]);

        return new CreateChatActionResult($result, isFresh: true);
    }

    private function createNewChat(
        PromptInput $input,
        PromptPersona $persona,
        string $question,
        bool $refreshEnabled,
        bool $isPrivate,
    ): Chat {
        $this->enforceRateLimit();

        $blockchainData = $this->blockchain->getBlockchainData($input);
        $cleanQuestion = $this->userInputSanitizer->sanitize($question);

        $aiResponse = $refreshEnabled
            ? $this->generateAiResponse($blockchainData, $input, $persona, $cleanQuestion)
            : $this->findOrGenerateAiResponse($input, $persona, $question, $blockchainData, $cleanQuestion);

        return $this->repository->createChat(
            $input,
            $aiResponse,
            $blockchainData->current(),
            $persona,
            $cleanQuestion,
            $isPrivate
        );
    }

    private function enforceRateLimit(): void
    {
        $key = "openai:{$this->trackingId}";

        if (RateLimiter::tooManyAttempts($key, $this->maxOpenAIAttempts)) {
            throw new ThrottleRequestsException(
                "You have reached the daily OpenAI limit of {$this->maxOpenAIAttempts} requests."
            );
        }

        RateLimiter::hit($key, self::RATE_LIMIT_SECONDS);
    }

    private function generateAiResponse(
        BlockchainData $blockchainData,
        PromptInput $input,
        PromptPersona $persona,
        string $cleanQuestion,
    ): string {
        $additional = $this->contextBuilder->build($blockchainData, $input, $cleanQuestion);

        return $this->openai->generateText($blockchainData, $input, $persona, $cleanQuestion, null, $additional);
    }

    private function findOrGenerateAiResponse(
        PromptInput $input,
        PromptPersona $persona,
        string $question,
        BlockchainData $blockchainData,
        string $cleanQuestion,
    ): string {
        return $this->messageRepository->findAssistantMessage($input, $persona, $question)->content
            ?? $this->generateAiResponse($blockchainData, $input, $persona, $cleanQuestion);
    }
}
