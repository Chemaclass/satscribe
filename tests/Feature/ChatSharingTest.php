<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Chat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The share toggle reported its own outcome as a constant, so the client had no
 * way to tell whether the flag it just sent was applied.
 */
final class ChatSharingTest extends TestCase
{
    use RefreshDatabase;

    public function test_sharing_a_chat_reports_it_as_shared(): void
    {
        $chat = $this->ownedChat();

        $response = $this->withSession(['nostr_pubkey' => 'owner'])
            ->postJson(route('chat.share', $chat), ['shared' => true]);

        $response->assertStatus(200);
        $response->assertJsonPath('shared', true);
        self::assertTrue($chat->refresh()->is_shared);
    }

    public function test_unsharing_a_chat_reports_it_as_not_shared(): void
    {
        $chat = $this->ownedChat(shared: true);

        $response = $this->withSession(['nostr_pubkey' => 'owner'])
            ->postJson(route('chat.share', $chat), ['shared' => false]);

        $response->assertStatus(200);
        $response->assertJsonPath('shared', false);
        self::assertFalse($chat->refresh()->is_shared);
    }

    /**
     * A form-encoded body carries "false" as a string, which casts to true.
     */
    public function test_the_string_false_does_not_share_the_chat(): void
    {
        $chat = $this->ownedChat(shared: true);

        $response = $this->withSession(['nostr_pubkey' => 'owner'])
            ->post(route('chat.share', $chat), ['shared' => 'false']);

        $response->assertStatus(200);
        self::assertFalse($chat->refresh()->is_shared);
    }

    public function test_a_stranger_cannot_share_someone_elses_chat(): void
    {
        $chat = $this->ownedChat();

        $this->withSession(['nostr_pubkey' => 'someone-else'])
            ->postJson(route('chat.share', $chat), ['shared' => true])
            ->assertStatus(403);

        self::assertFalse($chat->refresh()->is_shared);
    }

    private function ownedChat(bool $shared = false): Chat
    {
        return Chat::create([
            'title' => 'Test Chat',
            'tracking_id' => 'owner',
            'is_public' => false,
            'is_shared' => $shared,
        ]);
    }
}
