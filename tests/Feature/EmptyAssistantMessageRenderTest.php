<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Chat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Chats saved before the empty-response guard still hold blank assistant
 * messages. Rendering one produced an empty bubble with a Copy button and no
 * explanation, so the page looked broken rather than finished.
 */
final class EmptyAssistantMessageRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_empty_assistant_message_explains_itself(): void
    {
        $chat = $this->chatWithAssistantContent('');

        $response = $this->withSession(['nostr_pubkey' => 'owner'])->get(route('chat.show', $chat));

        $response->assertStatus(200);
        $response->assertSee(__('home.stream.empty'));
    }

    public function test_a_normal_assistant_message_shows_its_content(): void
    {
        $chat = $this->chatWithAssistantContent('Block 10 was mined in 2009.');

        $response = $this->withSession(['nostr_pubkey' => 'owner'])->get(route('chat.show', $chat));

        $response->assertStatus(200);
        $response->assertSee('Block 10 was mined in 2009.');
        $response->assertDontSee(__('home.stream.empty'));
    }

    private function chatWithAssistantContent(string $content): Chat
    {
        $chat = Chat::create([
            'title' => 'Test Chat',
            'tracking_id' => 'owner',
            'is_public' => false,
            'is_shared' => false,
        ]);

        $chat->addUserMessage('Give me a generic overview.', [
            'type' => 'block',
            'input' => '10',
            'persona' => 'storyteller',
        ]);
        $chat->addAssistantMessage($content);

        return $chat;
    }
}
