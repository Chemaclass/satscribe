<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Chat;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blockchain\Domain\BlockchainFacadeInterface;
use Modules\Chat\Application\ChatService;
use Modules\Chat\Application\SuggestedPromptService;
use Modules\Chat\Domain\AddMessageActionInterface;
use Modules\Chat\Domain\CreateChatActionInterface;
use Modules\Chat\Domain\Repository\MessageRepositoryInterface;
use Modules\Shared\Domain\Enum\Chat\PromptPersona;
use Tests\TestCase;

final class ChatServiceTest extends TestCase
{
    use RefreshDatabase;

    private BlockchainFacadeInterface $blockchainFacade;
    private CreateChatActionInterface $createChatAction;
    private AddMessageActionInterface $addMessageAction;
    private MessageRepositoryInterface $messageRepository;
    private SuggestedPromptService $promptService;
    private ChatService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->blockchainFacade = $this->createMock(BlockchainFacadeInterface::class);
        $this->createChatAction = $this->createMock(CreateChatActionInterface::class);
        $this->addMessageAction = $this->createMock(AddMessageActionInterface::class);
        $this->messageRepository = $this->createMock(MessageRepositoryInterface::class);
        $this->promptService = new SuggestedPromptService();

        $this->service = new ChatService(
            $this->blockchainFacade,
            $this->createChatAction,
            $this->addMessageAction,
            $this->messageRepository,
            $this->promptService,
        );
    }

    public function test_get_chat_data_returns_expected_keys(): void
    {
        $chat = $this->createChatWithMessages();

        $this->blockchainFacade->method('getMaxPossibleBlockHeight')->willReturn(800001);
        $this->blockchainFacade->method('getCurrentBlockHeight')->willReturn(800000);

        $result = $this->service->getChatData($chat);

        $this->assertArrayHasKey('questionPlaceholder', $result);
        $this->assertArrayHasKey('suggestedPromptsGrouped', $result);
        $this->assertArrayHasKey('suggestions', $result);
        $this->assertArrayHasKey('maxBitcoinBlockHeight', $result);
        $this->assertArrayHasKey('latestBlockHeight', $result);
        $this->assertArrayHasKey('personaDescriptions', $result);
        $this->assertArrayHasKey('question', $result);
        $this->assertArrayHasKey('chat', $result);
        $this->assertArrayHasKey('search', $result);
        $this->assertArrayHasKey('persona', $result);
    }

    public function test_get_chat_data_includes_correct_block_height(): void
    {
        $chat = $this->createChatWithMessages();

        $this->blockchainFacade->method('getMaxPossibleBlockHeight')->willReturn(800001);
        $this->blockchainFacade->method('getCurrentBlockHeight')->willReturn(800000);

        $result = $this->service->getChatData($chat);

        $this->assertSame(800001, $result['maxBitcoinBlockHeight']);
        $this->assertSame(800000, $result['latestBlockHeight']);
    }

    /**
     * Only the landing page shows the counter, and it reads totalMessages. The
     * chat page used to run a full table count for a value it never rendered.
     */
    public function test_get_index_data_counts_messages_once(): void
    {
        $this->blockchainFacade->method('getMaxPossibleBlockHeight')->willReturn(800001);
        $this->blockchainFacade->method('getCurrentBlockHeight')->willReturn(800000);
        $this->messageRepository->expects($this->once())
            ->method('countAll')
            ->willReturn(250);

        $result = $this->service->getIndexData();

        $this->assertSame(250, $result['totalMessages']);
    }

    public function test_get_chat_data_does_not_count_anything(): void
    {
        $chat = $this->createChatWithMessages();

        $this->blockchainFacade->method('getMaxPossibleBlockHeight')->willReturn(800001);
        $this->blockchainFacade->method('getCurrentBlockHeight')->willReturn(800000);
        $this->messageRepository->expects($this->never())->method('countAll');

        $result = $this->service->getChatData($chat);

        $this->assertArrayNotHasKey('totalChats', $result);
    }

    public function test_add_message_calls_action_and_returns_response(): void
    {
        $chat = $this->createChatWithMessages();
        $message = 'What is the fee?';

        $this->addMessageAction->expects($this->once())
            ->method('execute')
            ->with($chat, $message);

        $result = $this->service->addMessage($chat, $message);

        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('suggestions', $result);
    }

    public function test_get_index_data_returns_expected_keys(): void
    {
        $this->blockchainFacade->method('getMaxPossibleBlockHeight')->willReturn(800001);
        $this->blockchainFacade->method('getCurrentBlockHeight')->willReturn(800000);

        $result = $this->service->getIndexData();

        $this->assertArrayHasKey('questionPlaceholder', $result);
        $this->assertArrayHasKey('suggestedPromptsGrouped', $result);
        $this->assertArrayHasKey('maxBitcoinBlockHeight', $result);
        $this->assertArrayHasKey('latestBlockHeight', $result);
        $this->assertArrayHasKey('personaDescriptions', $result);
        $this->assertArrayHasKey('totalMessages', $result);
    }

    public function test_get_index_data_includes_correct_block_heights(): void
    {
        $this->blockchainFacade->method('getMaxPossibleBlockHeight')->willReturn(850001);
        $this->blockchainFacade->method('getCurrentBlockHeight')->willReturn(850000);

        $result = $this->service->getIndexData();

        $this->assertSame(850001, $result['maxBitcoinBlockHeight']);
        $this->assertSame(850000, $result['latestBlockHeight']);
    }

    public function test_get_index_data_includes_persona_descriptions(): void
    {
        $this->blockchainFacade->method('getMaxPossibleBlockHeight')->willReturn(800001);
        $this->blockchainFacade->method('getCurrentBlockHeight')->willReturn(800000);

        $result = $this->service->getIndexData();

        $this->assertIsString($result['personaDescriptions']);
    }

    private function createChatWithMessages(): Chat
    {
        $chat = Chat::create(['ulid' => 'test-ulid', 'is_public' => true]);

        Message::create([
            'chat_id' => $chat->id,
            'role' => 'user',
            'content' => 'What is this transaction?',
            'meta' => [
                'type' => 'transaction',
                'input' => 'abc123txid',
                'persona' => PromptPersona::Educator->value,
            ],
        ]);

        Message::create([
            'chat_id' => $chat->id,
            'role' => 'assistant',
            'content' => 'This transaction transfers 1 BTC.',
            'meta' => [],
        ]);

        return $chat->refresh();
    }
}
