<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Chat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ChatVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_private_chat(): void
    {
        $chat = $this->createChat(['tracking_id' => 'owner']);

        $response = $this->withSession(['nostr_pubkey' => 'owner'])->get(route('chat.show', $chat));

        $response->assertStatus(200);
    }

    public function test_guest_cannot_view_private_unshared_chat(): void
    {
        $chat = $this->createChat(['tracking_id' => 'owner']);

        $response = $this->withSession(['nostr_pubkey' => 'guest'])->get(route('chat.show', $chat));

        $response->assertStatus(403);
    }

    public function test_guest_can_view_public_chat(): void
    {
        $chat = $this->createChat(['tracking_id' => 'owner', 'is_public' => true]);

        $response = $this->withSession(['nostr_pubkey' => 'guest'])->get(route('chat.show', $chat));

        $response->assertStatus(200);
    }

    public function test_guest_can_view_shared_private_chat(): void
    {
        $chat = $this->createChat(['tracking_id' => 'owner', 'is_shared' => true]);

        $response = $this->withSession(['nostr_pubkey' => 'guest'])->get(route('chat.show', $chat));

        $response->assertStatus(200);
    }

    private function createChat(array $attrs): Chat
    {
        $chat = Chat::create(array_merge([
            'title' => 'Test Chat',
            'tracking_id' => 'owner',
            'is_public' => false,
            'is_shared' => false,
        ], $attrs));

        $chat->addUserMessage('question', ['type' => 'transaction', 'input' => 'abc', 'persona' => 'storyteller']);
        $chat->addAssistantMessage('answer');

        return $chat;
    }
}
