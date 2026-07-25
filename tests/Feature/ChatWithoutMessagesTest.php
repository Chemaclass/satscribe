<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Chat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * getChatData() asserted the first message was non-null with a docblock rather
 * than a check, so a chat row without messages met a "call to a member function
 * on null" and returned 500 instead of a not-found.
 */
final class ChatWithoutMessagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_chat_without_messages_is_not_found(): void
    {
        $chat = Chat::create([
            'title' => 'Orphan',
            'tracking_id' => 'owner',
            'is_public' => false,
            'is_shared' => false,
        ]);

        $this->withSession(['nostr_pubkey' => 'owner'])
            ->get(route('chat.show', $chat))
            ->assertStatus(404);
    }

    public function test_a_normal_chat_still_renders(): void
    {
        $chat = Chat::create([
            'title' => 'Test Chat',
            'tracking_id' => 'owner',
            'is_public' => false,
            'is_shared' => false,
        ]);

        $chat->addUserMessage('question', ['type' => 'block', 'input' => '210000', 'persona' => 'storyteller']);
        $chat->addAssistantMessage('answer');

        $this->withSession(['nostr_pubkey' => 'owner'])
            ->get(route('chat.show', $chat))
            ->assertStatus(200)
            ->assertSee('Block #210000');
    }
}
