<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Chat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Chat\Infrastructure\Repository\ChatRepository;
use Tests\TestCase;

final class ChatRepositoryPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_pagination_eager_loads_messages(): void
    {
        $trackingId = 'test-tracking-id';

        // Create a chat with messages
        $chat = Chat::create([
            'title' => 'Test Chat',
            'tracking_id' => $trackingId,
            'is_public' => false,
            'is_shared' => false,
        ]);

        $chat->addUserMessage('question', ['type' => 'transaction', 'input' => 'abc', 'persona' => 'storyteller']);
        $chat->addAssistantMessage('answer', ['type' => 'transaction', 'input' => 'abc', 'persona' => 'storyteller']);

        $repository = new ChatRepository(perPage: 10, trackingId: $trackingId);

        $paginator = $repository->getPagination(showAll: false);

        /** @var Chat $loadedChat */
        $loadedChat = $paginator->items()[0];

        $this->assertTrue($loadedChat->relationLoaded('messages'));
        $this->assertCount(2, $loadedChat->messages);
    }

    public function test_get_pagination_with_show_all_includes_public_chats(): void
    {
        $trackingId = 'test-tracking-id';

        // Create owned private chat
        $ownedChat = Chat::create([
            'title' => 'Owned Chat',
            'tracking_id' => $trackingId,
            'is_public' => false,
            'is_shared' => false,
        ]);
        $ownedChat->addUserMessage('question', ['type' => 'transaction', 'input' => 'abc', 'persona' => 'storyteller']);
        $ownedChat->addAssistantMessage('answer');

        // Create public chat from another user
        $publicChat = Chat::create([
            'title' => 'Public Chat',
            'tracking_id' => 'other-user',
            'is_public' => true,
            'is_shared' => false,
        ]);
        $publicChat->addUserMessage('question', ['type' => 'transaction', 'input' => 'xyz', 'persona' => 'storyteller']);
        $publicChat->addAssistantMessage('answer');

        $repository = new ChatRepository(perPage: 10, trackingId: $trackingId);

        $paginator = $repository->getPagination(showAll: true);

        $this->assertCount(2, $paginator->items());

        // Both chats should have messages eager loaded
        foreach ($paginator->items() as $chat) {
            $this->assertTrue($chat->relationLoaded('messages'));
        }
    }
}
