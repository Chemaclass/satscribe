<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Chat;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Chat\Infrastructure\Repository\MessageRepository;
use Modules\Shared\Domain\Data\Chat\PromptInput;
use Modules\Shared\Domain\Enum\Chat\PromptPersona;
use Modules\Shared\Domain\Enum\Chat\PromptType;
use Tests\TestCase;

final class MessageRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_find_assistant_message_returns_matching_message(): void
    {
        $chat = Chat::create(['ulid' => 'test-ulid', 'is_public' => true]);

        Message::create([
            'chat_id' => $chat->id,
            'role' => 'assistant',
            'content' => 'This is the response',
            'meta' => [
                'type' => 'transaction',
                'input' => 'abc123txid',
                'persona' => 'educator',
                'question' => 'What is this?',
            ],
        ]);

        $repository = new MessageRepository();
        $input = new PromptInput(PromptType::Transaction, 'abc123txid');

        $result = $repository->findAssistantMessage($input, PromptPersona::Educator, 'What is this?');

        $this->assertNotNull($result);
        $this->assertSame('This is the response', $result->content);
    }

    public function test_find_assistant_message_returns_null_when_no_match(): void
    {
        $chat = Chat::create(['ulid' => 'test-ulid', 'is_public' => true]);

        Message::create([
            'chat_id' => $chat->id,
            'role' => 'assistant',
            'content' => 'This is the response',
            'meta' => [
                'type' => 'transaction',
                'input' => 'abc123txid',
                'persona' => 'educator',
                'question' => 'What is this?',
            ],
        ]);

        $repository = new MessageRepository();
        $input = new PromptInput(PromptType::Transaction, 'different-txid');

        $result = $repository->findAssistantMessage($input, PromptPersona::Educator, 'What is this?');

        $this->assertNull($result);
    }

    public function test_find_assistant_message_ignores_user_messages(): void
    {
        $chat = Chat::create(['ulid' => 'test-ulid', 'is_public' => true]);

        Message::create([
            'chat_id' => $chat->id,
            'role' => 'user',
            'content' => 'User message',
            'meta' => [
                'type' => 'transaction',
                'input' => 'abc123txid',
                'persona' => 'educator',
                'question' => 'What is this?',
            ],
        ]);

        $repository = new MessageRepository();
        $input = new PromptInput(PromptType::Transaction, 'abc123txid');

        $result = $repository->findAssistantMessage($input, PromptPersona::Educator, 'What is this?');

        $this->assertNull($result);
    }

    public function test_find_assistant_message_matches_persona(): void
    {
        $chat = Chat::create(['ulid' => 'test-ulid', 'is_public' => true]);

        Message::create([
            'chat_id' => $chat->id,
            'role' => 'assistant',
            'content' => 'Educator response',
            'meta' => [
                'type' => 'transaction',
                'input' => 'abc123txid',
                'persona' => 'educator',
                'question' => 'What is this?',
            ],
        ]);

        $repository = new MessageRepository();
        $input = new PromptInput(PromptType::Transaction, 'abc123txid');

        // Different persona should not match
        $result = $repository->findAssistantMessage($input, PromptPersona::Developer, 'What is this?');

        $this->assertNull($result);
    }

    public function test_find_assistant_message_matches_type(): void
    {
        $chat = Chat::create(['ulid' => 'test-ulid', 'is_public' => true]);

        Message::create([
            'chat_id' => $chat->id,
            'role' => 'assistant',
            'content' => 'Transaction response',
            'meta' => [
                'type' => 'transaction',
                'input' => '800000',
                'persona' => 'educator',
                'question' => 'What is this?',
            ],
        ]);

        $repository = new MessageRepository();
        // Same input but different type
        $input = new PromptInput(PromptType::Block, '800000');

        $result = $repository->findAssistantMessage($input, PromptPersona::Educator, 'What is this?');

        $this->assertNull($result);
    }

    public function test_find_assistant_message_matches_question(): void
    {
        $chat = Chat::create(['ulid' => 'test-ulid', 'is_public' => true]);

        Message::create([
            'chat_id' => $chat->id,
            'role' => 'assistant',
            'content' => 'Response to question 1',
            'meta' => [
                'type' => 'transaction',
                'input' => 'abc123txid',
                'persona' => 'educator',
                'question' => 'What is this?',
            ],
        ]);

        $repository = new MessageRepository();
        $input = new PromptInput(PromptType::Transaction, 'abc123txid');

        // Different question should not match
        $result = $repository->findAssistantMessage($input, PromptPersona::Educator, 'Different question');

        $this->assertNull($result);
    }

    /**
     * The lookup serves a previous answer as a cached reply. With several rows
     * matching the same key the query took whichever the database happened to
     * return first, so the reply could differ between requests. The newest is
     * the one generated against the most recent chain state.
     */
    public function test_find_assistant_message_returns_the_newest_match(): void
    {
        $chat = Chat::create(['ulid' => 'test-ulid', 'is_public' => true]);

        $meta = [
            'type' => 'transaction',
            'input' => 'abc123txid',
            'persona' => 'educator',
            'question' => 'What is this?',
        ];

        foreach (['oldest answer', 'middle answer', 'newest answer'] as $content) {
            Message::create([
                'chat_id' => $chat->id,
                'role' => 'assistant',
                'content' => $content,
                'meta' => $meta,
            ]);
        }

        $result = (new MessageRepository())->findAssistantMessage(
            new PromptInput(PromptType::Transaction, 'abc123txid'),
            PromptPersona::Educator,
            'What is this?',
        );

        $this->assertNotNull($result);
        $this->assertSame('newest answer', $result->content);
    }

    public function test_count_all_counts_every_message(): void
    {
        $chat = Chat::create(['ulid' => 'test-ulid', 'is_public' => true]);

        foreach (['user', 'assistant', 'user'] as $role) {
            Message::create([
                'chat_id' => $chat->id,
                'role' => $role,
                'content' => 'content',
                'meta' => [],
            ]);
        }

        $this->assertSame(3, (new MessageRepository())->countAll());
    }
}
