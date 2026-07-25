<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Chat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * history/{id}/raw took an integer id and returned that message's stored
 * payload with no ownership check at all, so a private chat's data was reachable
 * by anyone willing to count upwards. It now follows the same rule as viewing
 * the chat itself.
 */
final class RawMessageAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_owner_can_read_their_own_raw_data(): void
    {
        $chat = $this->chatWithRawData();
        $messageId = $chat->messages()->where('role', 'assistant')->firstOrFail()->id;

        $response = $this->withSession(['nostr_pubkey' => 'owner'])
            ->getJson(route('history.get-raw', $messageId));

        $response->assertStatus(200);
        $response->assertJsonPath('height', 210000);
    }

    public function test_a_stranger_cannot_read_raw_data_of_a_private_chat(): void
    {
        $chat = $this->chatWithRawData();
        $messageId = $chat->messages()->where('role', 'assistant')->firstOrFail()->id;

        $this->withSession(['nostr_pubkey' => 'someone-else'])
            ->getJson(route('history.get-raw', $messageId))
            ->assertStatus(403);
    }

    public function test_a_stranger_can_read_raw_data_of_a_public_chat(): void
    {
        $chat = $this->chatWithRawData(public: true);
        $messageId = $chat->messages()->where('role', 'assistant')->firstOrFail()->id;

        $this->withSession(['nostr_pubkey' => 'someone-else'])
            ->getJson(route('history.get-raw', $messageId))
            ->assertStatus(200);
    }

    public function test_an_unknown_message_is_not_found(): void
    {
        $this->withSession(['nostr_pubkey' => 'owner'])
            ->getJson(route('history.get-raw', 999999))
            ->assertStatus(404);
    }

    private function chatWithRawData(bool $public = false): Chat
    {
        $chat = Chat::create([
            'title' => 'Test Chat',
            'tracking_id' => 'owner',
            'is_public' => $public,
            'is_shared' => false,
        ]);

        $chat->addUserMessage('question', ['type' => 'block', 'input' => '210000', 'persona' => 'storyteller']);
        $chat->addAssistantMessage('answer', ['raw_data' => ['height' => 210000]]);

        return $chat;
    }
}
