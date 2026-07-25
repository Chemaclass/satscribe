<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Chat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use function is_string;

/**
 * The chat views carry the streaming UI, which has no other coverage. These
 * render them end to end and pin the one thing that silently drifted before:
 * the chat header must label an input the same way the backend classified it.
 */
final class HomePageRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders_with_examples_and_detected_badge(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('example-card', escape: false);
        $response->assertSee('search-detected', escape: false);

        $this->dumpForJsLinting((string) $response->getContent());
    }

    public function test_chat_header_labels_a_block_height_as_a_block(): void
    {
        $chat = $this->createChat('210000');

        $response = $this->withSession(['nostr_pubkey' => 'owner'])->get(route('chat.show', $chat));

        $response->assertStatus(200);
        $response->assertSee('Block #210000');
        $response->assertSee('data-lucide="box"', escape: false);
    }

    /**
     * The old client-side heuristic ("shorter than 10 chars") called this a
     * block while the server fetched it as a transaction.
     */
    public function test_chat_header_labels_a_short_non_numeric_input_as_a_transaction(): void
    {
        $chat = $this->createChat('deadbeef');

        $response = $this->withSession(['nostr_pubkey' => 'owner'])->get(route('chat.show', $chat));

        $response->assertStatus(200);
        $response->assertSee('Transaction deadbeef');
        $response->assertSee('data-lucide="arrow-right-left"', escape: false);
    }

    public function test_chat_page_exposes_a_stop_button_for_streaming(): void
    {
        $chat = $this->createChat('210000');

        $response = $this->withSession(['nostr_pubkey' => 'owner'])->get(route('chat.show', $chat));

        $response->assertSee('stop-streaming-btn', escape: false);
    }

    private function createChat(string $input): Chat
    {
        $chat = Chat::create([
            'title' => 'Test Chat',
            'tracking_id' => 'owner',
            'is_public' => false,
            'is_shared' => false,
        ]);

        $chat->addUserMessage('question', ['type' => 'block', 'input' => $input, 'persona' => 'storyteller']);
        $chat->addAssistantMessage('answer');

        return $chat;
    }

    /**
     * Opt-in escape hatch: `SATSCRIBE_DUMP_HOME=/path vendor/bin/phpunit` writes
     * the rendered page so the inline streaming scripts can be run through
     * `node --check`, which is the only way to catch a syntax error in them.
     */
    private function dumpForJsLinting(string $html): void
    {
        $target = getenv('SATSCRIBE_DUMP_HOME');

        if (is_string($target) && $target !== '') {
            file_put_contents($target, $html);
        }
    }
}
