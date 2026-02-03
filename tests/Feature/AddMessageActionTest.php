<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Chat;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Blockchain\Domain\BlockchainFacadeInterface;
use Modules\Chat\Application\AdditionalContextBuilder;
use Modules\Chat\Application\AddMessageAction;
use Modules\Chat\Domain\Data\UserInputSanitizer;
use Modules\Chat\Domain\Repository\ChatRepositoryInterface;
use Modules\Chat\Domain\Repository\FlaggedWordRepositoryInterface;
use Modules\Chat\Domain\Repository\MessageRepositoryInterface;
use Modules\OpenAI\Domain\OpenAIFacadeInterface;
use Modules\Shared\Domain\Data\Blockchain\BlockchainData;
use Modules\Shared\Domain\Data\Blockchain\TransactionData;
use Modules\Shared\Domain\Enum\Chat\PromptPersona;
use Modules\UtxoTrace\Domain\UtxoTraceFacadeInterface;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class AddMessageActionTest extends TestCase
{
    use RefreshDatabase;

    private BlockchainFacadeInterface $blockchainFacade;
    private OpenAIFacadeInterface $openaiFacade;
    private ChatRepositoryInterface $chatRepository;
    private MessageRepositoryInterface $messageRepository;
    private UserInputSanitizer $sanitizer;
    private AdditionalContextBuilder $contextBuilder;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->blockchainFacade = $this->createMock(BlockchainFacadeInterface::class);
        $this->openaiFacade = $this->createMock(OpenAIFacadeInterface::class);
        $this->chatRepository = $this->createMock(ChatRepositoryInterface::class);
        $this->messageRepository = $this->createMock(MessageRepositoryInterface::class);
        $this->logger = $this->createStub(LoggerInterface::class);

        // Create real instances with stubbed dependencies
        $flaggedWordRepo = $this->createStub(FlaggedWordRepositoryInterface::class);
        $flaggedWordRepo->method('getAllWords')->willReturn([]);
        $this->sanitizer = new UserInputSanitizer($flaggedWordRepo);

        $utxoTraceFacade = $this->createStub(UtxoTraceFacadeInterface::class);
        $this->contextBuilder = new AdditionalContextBuilder($this->blockchainFacade, $utxoTraceFacade);

        RateLimiter::clear('openai:test-tracking-id');
    }

    public function test_execute_adds_message_to_chat(): void
    {
        $chat = $this->createChatWithFirstMessage();
        $message = 'Follow up question';

        $blockchainData = $this->createBlockchainData();

        $this->messageRepository->expects($this->once())
            ->method('findAssistantMessage')
            ->willReturn(null);

        $this->blockchainFacade->expects($this->once())
            ->method('getBlockchainData')
            ->willReturn($blockchainData);

        $this->openaiFacade->expects($this->once())
            ->method('generateText')
            ->willReturn('AI follow-up response');

        $this->chatRepository->expects($this->once())
            ->method('addMessageToChat')
            ->with($chat, $message, 'AI follow-up response');

        $action = $this->createAction();

        $action->execute($chat, $message);
    }

    public function test_execute_uses_cached_message_when_available(): void
    {
        $chat = $this->createChatWithFirstMessage();
        $message = 'Follow up question';

        $cachedMessage = new Message();
        $cachedMessage->content = 'Cached AI response';

        $this->messageRepository->expects($this->once())
            ->method('findAssistantMessage')
            ->willReturn($cachedMessage);

        // Should NOT call OpenAI when cached message exists
        $this->openaiFacade->expects($this->never())
            ->method('generateText');

        // Should NOT call blockchain facade when cached message exists
        $this->blockchainFacade->expects($this->never())
            ->method('getBlockchainData');

        $this->chatRepository->expects($this->once())
            ->method('addMessageToChat')
            ->with($chat, $message, 'Cached AI response');

        $action = $this->createAction();

        $action->execute($chat, $message);
    }

    public function test_execute_throws_when_rate_limit_exceeded(): void
    {
        $chat = $this->createChatWithFirstMessage();
        $message = 'Follow up question';

        // Pre-fill rate limiter to exceed the limit
        $key = 'openai:test-tracking-id';
        RateLimiter::hit($key, 86400);
        RateLimiter::hit($key, 86400);

        // Create action with max 1 attempt (already have 2 hits)
        $action = $this->createAction(maxOpenAIAttempts: 1);

        $this->expectException(ThrottleRequestsException::class);
        $this->expectExceptionMessage('You have reached the daily OpenAI limit');

        $action->execute($chat, $message);
    }

    public function test_execute_sanitizes_urls_from_user_input(): void
    {
        $chat = $this->createChatWithFirstMessage();
        $message = 'Check https://evil.com for info';

        $blockchainData = $this->createBlockchainData();

        $this->messageRepository->method('findAssistantMessage')->willReturn(null);
        $this->blockchainFacade->method('getBlockchainData')->willReturn($blockchainData);
        $this->openaiFacade->method('generateText')->willReturn('AI response');

        // The addMessageToChat should receive sanitized message with [link removed]
        $this->chatRepository->expects($this->once())
            ->method('addMessageToChat')
            ->with($chat, 'Check [link removed] for info', 'AI response');

        $action = $this->createAction();

        $action->execute($chat, $message);
    }

    public function test_execute_uses_persona_from_first_message(): void
    {
        $chat = $this->createChatWithFirstMessage(PromptPersona::Developer);
        $message = 'Follow up question';

        $blockchainData = $this->createBlockchainData();

        $this->messageRepository->method('findAssistantMessage')->willReturn(null);
        $this->blockchainFacade->method('getBlockchainData')->willReturn($blockchainData);

        $this->openaiFacade->expects($this->once())
            ->method('generateText')
            ->with(
                $this->anything(),
                $this->anything(),
                PromptPersona::Developer,
                $message,
                $chat,
                $this->anything(),
            )
            ->willReturn('Developer response');

        $this->chatRepository->method('addMessageToChat');

        $action = $this->createAction();

        $action->execute($chat, $message);
    }

    private function createAction(int $maxOpenAIAttempts = 1000): AddMessageAction
    {
        return new AddMessageAction(
            blockchainFacade: $this->blockchainFacade,
            openAIFacade: $this->openaiFacade,
            chatRepository: $this->chatRepository,
            messageRepository: $this->messageRepository,
            userInputSanitizer: $this->sanitizer,
            contextBuilder: $this->contextBuilder,
            logger: $this->logger,
            trackingId: 'test-tracking-id',
            maxOpenAIAttempts: $maxOpenAIAttempts,
        );
    }

    private function createChatWithFirstMessage(PromptPersona $persona = PromptPersona::Educator): Chat
    {
        $firstMessage = new Message();
        $firstMessage->role = 'user';
        $firstMessage->content = 'What is this transaction?';
        $firstMessage->meta = [
            'type' => 'transaction',
            'input' => 'abc123txid',
            'persona' => $persona->value,
        ];

        $chat = new Chat();
        $chat->id = 1;
        $chat->setRelation('messages', collect([$firstMessage]));

        return $chat;
    }

    private function createBlockchainData(): BlockchainData
    {
        $tx = new TransactionData(
            txid: 'abc123txid',
            version: 2,
            locktime: 0,
            vin: [],
            vout: [],
            size: 200,
            weight: 800,
            fee: 1000,
            confirmed: true,
            blockHeight: 800000,
            blockHash: 'blockhash123',
            blockTime: 1700000000,
        );

        return BlockchainData::forTransaction($tx, null);
    }
}
