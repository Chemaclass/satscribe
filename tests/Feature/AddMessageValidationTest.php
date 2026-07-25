<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Chat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Shared\Domain\Chat\ChatConstants;
use Tests\TestCase;

/**
 * Creating a chat caps `question` at 200 characters, but the follow-up
 * endpoints took a raw Request and read `message` with no bound at all — so the
 * text going into the model prompt, and the bill for it, was whatever the caller
 * chose to send.
 */
final class AddMessageValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_overlong_message_is_rejected(): void
    {
        $chat = $this->ownedChat();
        $message = str_repeat('a', ChatConstants::MAX_QUESTION_LENGTH + 1);

        $this->withSession(['nostr_pubkey' => 'owner'])
            ->postJson(route('chat.add-message-stream', $chat), ['message' => $message])
            ->assertStatus(422)
            ->assertJsonValidationErrors('message');
    }

    public function test_an_empty_message_is_rejected(): void
    {
        $chat = $this->ownedChat();

        $this->withSession(['nostr_pubkey' => 'owner'])
            ->postJson(route('chat.add-message-stream', $chat), ['message' => '   '])
            ->assertStatus(422)
            ->assertJsonValidationErrors('message');
    }

    public function test_an_array_message_is_rejected(): void
    {
        $chat = $this->ownedChat();

        $this->withSession(['nostr_pubkey' => 'owner'])
            ->postJson(route('chat.add-message-stream', $chat), ['message' => ['a', 'b']])
            ->assertStatus(422)
            ->assertJsonValidationErrors('message');
    }

    /**
     * Ownership is checked before validation, so a stranger learns nothing about
     * whether the chat exists.
     */
    public function test_a_stranger_is_refused_before_validation(): void
    {
        $chat = $this->ownedChat();

        $this->withSession(['nostr_pubkey' => 'someone-else'])
            ->postJson(route('chat.add-message-stream', $chat), ['message' => ''])
            ->assertStatus(403);
    }

    private function ownedChat(): Chat
    {
        $chat = Chat::create([
            'title' => 'Test Chat',
            'tracking_id' => 'owner',
            'is_public' => false,
            'is_shared' => false,
        ]);

        $chat->addUserMessage('question', ['type' => 'block', 'input' => '210000', 'persona' => 'storyteller']);
        $chat->addAssistantMessage('answer');

        return $chat;
    }
}
