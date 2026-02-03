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
}
