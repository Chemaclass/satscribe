<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Chat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Chat accessors read the eager-loaded relation when there is one and query
 * otherwise. Both have to agree on which message is first, so the query branch
 * orders explicitly rather than trusting the database's insertion order.
 */
final class ChatMessageOrderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_loaded_and_queried_paths_pick_the_same_messages(): void
    {
        $chat = Chat::create([
            'title' => 'Ordering',
            'tracking_id' => 'owner',
            'is_public' => false,
            'is_shared' => false,
        ]);

        $chat->addUserMessage('first question', ['type' => 'block', 'input' => '1']);
        $chat->addAssistantMessage('first answer');
        $chat->addUserMessage('second question', ['type' => 'block', 'input' => '2']);
        $chat->addAssistantMessage('second answer');

        $queried = Chat::findOrFail($chat->id);
        $loaded = Chat::with('messages')->findOrFail($chat->id);

        self::assertFalse($queried->relationLoaded('messages'));
        self::assertTrue($loaded->relationLoaded('messages'));

        foreach ([$queried, $loaded] as $subject) {
            self::assertSame('first question', $subject->getFirstUserMessage()->content);
            self::assertSame('second question', $subject->getLastUserMessage()->content);
            self::assertSame('first answer', $subject->getFirstAssistantMessage()->content);
            self::assertSame('second answer', $subject->getLastAssistantMessage()->content);
            self::assertSame('1', $subject->input);
        }
    }
}
