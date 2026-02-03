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
use Modules\Chat\Domain\Repository\ChatRepositoryInterface;
use Modules\Shared\Domain\Enum\Chat\PromptPersona;
use Tests\TestCase;

final class ChatServiceTest extends TestCase
{
    use RefreshDatabase;

    private BlockchainFacadeInterface $blockchainFacade;
    private CreateChatActionInterface $createChatAction;
    private AddMessageActionInterface $addMessageAction;
    private ChatRepositoryInterface $chatRepository;
    private SuggestedPromptService $promptService;
    private ChatService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->blockchainFacade = $this->createMock(BlockchainFacadeInterface::class);
        $this->createChatAction = $this->createMock(CreateChatActionInterface::class);
        $this->addMessageAction = $this->createMock(AddMessageActionInterface::class);
        $this->chatRepository = $this->createMock(ChatRepositoryInterface::class);
        $this->promptService = new SuggestedPromptService();

        $this->service = new ChatService(
            $this->blockchainFacade,
            $this->createChatAction,
            $this->addMessageAction,
            $this->chatRepository,
            $this->promptService,
        );
    }

    public function test_get_chat_data_returns_expected_keys(): void
    {
        $chat = $this->createChatWithMessages();

        $this->blockchainFacade->method('getMaxPossibleBlockHeight')->willReturn(800001);
        $this->blockchainFacade->method('getCurrentBlockHeight')->willReturn(800000);
        $this->chatRepository->method('getTotalChats')->willReturn(100);

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
        $this->assertArrayHasKey('totalChats', $result);
    }

    public function test_get_chat_data_includes_correct_block_height(): void
    {
        $chat = $this->createChatWithMessages();

        $this->blockchainFacade->method('getMaxPossibleBlockHeight')->willReturn(800001);
        $this->blockchainFacade->method('getCurrentBlockHeight')->willReturn(800000);
        $this->chatRepository->method('getTotalChats')->willReturn(100);

        $result = $this->service->getChatData($chat);

        $this->assertSame(800001, $result['maxBitcoinBlockHeight']);
        $this->assertSame(800000, $result['latestBlockHeight']);
    }

    public function test_get_chat_data_includes_total_chats(): void
    {
        $chat = $this->createChatWithMessages();

        $this->blockchainFacade->method('getMaxPossibleBlockHeight')->willReturn(800001);
        $this->blockchainFacade->method('getCurrentBlockHeight')->willReturn(800000);
        $this->chatRepository->expects($this->once())
            ->method('getTotalChats')
            ->willReturn(250);

        $result = $this->service->getChatData($chat);

        $this->assertSame(250, $result['totalChats']);
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

    public function test_get_index_data_returns_total_message_count(): void
    {
        // Create some messages
        $chat = Chat::create(['ulid' => 'test-ulid', 'is_public' => true]);
        Message::create([
            'chat_id' => $chat->id,
            'role' => 'user',
            'content' => 'Test message',
            'meta' => ['type' => 'transaction', 'input' => 'abc123', 'persona' => 'educator'],
        ]);
        Message::create([
            'chat_id' => $chat->id,
            'role' => 'assistant',
            'content' => 'Response',
            'meta' => [],
        ]);

        $this->blockchainFacade->method('getMaxPossibleBlockHeight')->willReturn(800001);
        $this->blockchainFacade->method('getCurrentBlockHeight')->willReturn(800000);

        $result = $this->service->getIndexData();

        $this->assertSame(2, $result['totalMessages']);
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

        return $chat->fresh();
    }
}
