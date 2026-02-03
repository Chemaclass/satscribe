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
use Modules\Chat\Application\CreateChatAction;
use Modules\Chat\Domain\Data\UserInputSanitizer;
use Modules\Chat\Domain\Repository\ChatRepositoryInterface;
use Modules\Chat\Domain\Repository\FlaggedWordRepositoryInterface;
use Modules\Chat\Domain\Repository\MessageRepositoryInterface;
use Modules\OpenAI\Domain\OpenAIFacadeInterface;
use Modules\Shared\Domain\Data\Blockchain\BlockchainData;
use Modules\Shared\Domain\Data\Blockchain\TransactionData;
use Modules\Shared\Domain\Data\Chat\PromptInput;
use Modules\Shared\Domain\Enum\Chat\PromptPersona;
use Modules\Shared\Domain\Enum\Chat\PromptType;
use Modules\UtxoTrace\Domain\UtxoTraceFacadeInterface;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class CreateChatActionTest extends TestCase
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

    public function test_execute_returns_cached_chat_when_exists_and_not_refresh(): void
    {
        $input = new PromptInput(PromptType::Transaction, 'abc123txid');
        $persona = PromptPersona::Educator;
        $question = 'What is this transaction?';

        $cachedChat = new Chat();
        $cachedChat->id = 1;
        $cachedChat->setRelation('messages', collect([
            $this->createMessage(['force_refresh' => false]),
        ]));

        $this->chatRepository->expects($this->once())
            ->method('findByCriteria')
            ->with($input, $persona, $question)
            ->willReturn($cachedChat);

        $action = $this->createAction();

        $result = $action->execute($input, $persona, $question, refreshEnabled: false);

        $this->assertSame($cachedChat, $result->chat);
        $this->assertFalse($result->isFresh);
    }

    public function test_execute_creates_new_chat_when_no_cache_exists(): void
    {
        $input = new PromptInput(PromptType::Transaction, 'abc123txid');
        $persona = PromptPersona::Educator;
        $question = 'What is this transaction?';

        $blockchainData = $this->createBlockchainData();
        $newChat = new Chat();
        $newChat->id = 2;

        $this->chatRepository->expects($this->once())
            ->method('findByCriteria')
            ->willReturn(null);

        $this->blockchainFacade->expects($this->once())
            ->method('getBlockchainData')
            ->with($input)
            ->willReturn($blockchainData);

        $this->messageRepository->expects($this->once())
            ->method('findAssistantMessage')
            ->willReturn(null);

        $this->openaiFacade->expects($this->once())
            ->method('generateText')
            ->willReturn('AI response');

        $this->chatRepository->expects($this->once())
            ->method('createChat')
            ->willReturn($newChat);

        $action = $this->createAction();

        $result = $action->execute($input, $persona, $question);

        $this->assertSame($newChat, $result->chat);
        $this->assertTrue($result->isFresh);
    }

    public function test_execute_creates_new_chat_when_refresh_enabled(): void
    {
        $input = new PromptInput(PromptType::Transaction, 'abc123txid');
        $persona = PromptPersona::Educator;
        $question = 'What is this transaction?';

        $blockchainData = $this->createBlockchainData();
        $newChat = new Chat();
        $newChat->id = 3;

        // Should NOT check for cached chat when refresh enabled
        $this->chatRepository->expects($this->never())
            ->method('findByCriteria');

        $this->blockchainFacade->expects($this->once())
            ->method('getBlockchainData')
            ->willReturn($blockchainData);

        // Should NOT check message repository when refresh enabled
        $this->messageRepository->expects($this->never())
            ->method('findAssistantMessage');

        $this->openaiFacade->expects($this->once())
            ->method('generateText')
            ->willReturn('Fresh AI response');

        $this->chatRepository->expects($this->once())
            ->method('createChat')
            ->willReturn($newChat);

        $action = $this->createAction();

        $result = $action->execute($input, $persona, $question, refreshEnabled: true);

        $this->assertSame($newChat, $result->chat);
        $this->assertTrue($result->isFresh);
    }

    public function test_execute_uses_cached_message_when_available(): void
    {
        $input = new PromptInput(PromptType::Transaction, 'abc123txid');
        $persona = PromptPersona::Educator;
        $question = 'What is this transaction?';

        $blockchainData = $this->createBlockchainData();
        $newChat = new Chat();

        $cachedMessage = new Message();
        $cachedMessage->content = 'Cached AI response';

        $this->chatRepository->expects($this->once())
            ->method('findByCriteria')
            ->willReturn(null);

        $this->blockchainFacade->expects($this->once())
            ->method('getBlockchainData')
            ->willReturn($blockchainData);

        $this->messageRepository->expects($this->once())
            ->method('findAssistantMessage')
            ->willReturn($cachedMessage);

        // Should NOT call OpenAI when cached message exists
        $this->openaiFacade->expects($this->never())
            ->method('generateText');

        $this->chatRepository->expects($this->once())
            ->method('createChat')
            ->with($input, 'Cached AI response', $this->anything(), $persona, $question, false)
            ->willReturn($newChat);

        $action = $this->createAction();

        $result = $action->execute($input, $persona, $question);

        $this->assertTrue($result->isFresh);
    }

    public function test_execute_throws_when_rate_limit_exceeded(): void
    {
        $input = new PromptInput(PromptType::Transaction, 'abc123txid');
        $persona = PromptPersona::Educator;
        $question = 'What is this transaction?';

        $this->chatRepository->expects($this->once())
            ->method('findByCriteria')
            ->willReturn(null);

        // Pre-fill rate limiter to exceed the limit
        $key = 'openai:test-tracking-id';
        RateLimiter::hit($key, 86400);
        RateLimiter::hit($key, 86400);

        // Create action with max 1 attempt (already have 2 hits)
        $action = $this->createAction(maxOpenAIAttempts: 1);

        $this->expectException(ThrottleRequestsException::class);
        $this->expectExceptionMessage('You have reached the daily OpenAI limit');

        $action->execute($input, $persona, $question);
    }

    public function test_execute_creates_public_chat_when_flag_enabled(): void
    {
        $input = new PromptInput(PromptType::Transaction, 'abc123txid');
        $persona = PromptPersona::Educator;
        $question = 'What is this transaction?';

        $blockchainData = $this->createBlockchainData();
        $newChat = new Chat();

        $this->chatRepository->method('findByCriteria')->willReturn(null);
        $this->blockchainFacade->method('getBlockchainData')->willReturn($blockchainData);
        $this->messageRepository->method('findAssistantMessage')->willReturn(null);
        $this->openaiFacade->method('generateText')->willReturn('AI response');

        $this->chatRepository->expects($this->once())
            ->method('createChat')
            ->with($input, 'AI response', $this->anything(), $persona, $question, true)
            ->willReturn($newChat);

        $action = $this->createAction();

        $result = $action->execute($input, $persona, $question, refreshEnabled: false, isPublic: true);

        $this->assertTrue($result->isFresh);
    }

    public function test_execute_sanitizes_urls_from_user_input(): void
    {
        $input = new PromptInput(PromptType::Transaction, 'abc123txid');
        $persona = PromptPersona::Educator;
        $question = 'Check https://evil.com for details';

        $blockchainData = $this->createBlockchainData();
        $newChat = new Chat();

        $this->chatRepository->method('findByCriteria')->willReturn(null);
        $this->blockchainFacade->method('getBlockchainData')->willReturn($blockchainData);
        $this->messageRepository->method('findAssistantMessage')->willReturn(null);
        $this->openaiFacade->method('generateText')->willReturn('AI response');

        // The createChat should receive sanitized question with [link removed]
        $this->chatRepository->expects($this->once())
            ->method('createChat')
            ->with(
                $input,
                'AI response',
                $this->anything(),
                $persona,
                'Check [link removed] for details',
                false,
            )
            ->willReturn($newChat);

        $action = $this->createAction();

        $action->execute($input, $persona, $question);
    }

    private function createAction(int $maxOpenAIAttempts = 1000): CreateChatAction
    {
        return new CreateChatAction(
            blockchainFacade: $this->blockchainFacade,
            openaiFacade: $this->openaiFacade,
            repository: $this->chatRepository,
            messageRepository: $this->messageRepository,
            userInputSanitizer: $this->sanitizer,
            contextBuilder: $this->contextBuilder,
            logger: $this->logger,
            trackingId: 'test-tracking-id',
            maxOpenAIAttempts: $maxOpenAIAttempts,
        );
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

    private function createMessage(array $meta = []): Message
    {
        $message = new Message();
        $message->role = 'assistant';
        $message->content = 'Test response';
        $message->meta = $meta;

        return $message;
    }
}
