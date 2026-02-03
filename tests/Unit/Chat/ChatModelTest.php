<?php

declare(strict_types=1);

namespace Tests\Unit\Chat;

use App\Models\Chat;
use App\Models\Message;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PHPUnit\Framework\TestCase;

final class ChatModelTest extends TestCase
{
    public function test_get_first_user_message_uses_loaded_relation(): void
    {
        $userMessage = new Message();
        $userMessage->role = 'user';
        $userMessage->content = 'user question';

        $assistantMessage = new Message();
        $assistantMessage->role = 'assistant';
        $assistantMessage->content = 'assistant response';

        $chat = new Chat();
        $chat->setRelation('messages', new Collection([$userMessage, $assistantMessage]));

        $result = $chat->getFirstUserMessage();

        $this->assertSame('user', $result->role);
        $this->assertSame('user question', $result->content);
    }

    public function test_get_first_user_message_throws_when_no_user_message_in_loaded_relation(): void
    {
        $assistantMessage = new Message();
        $assistantMessage->role = 'assistant';
        $assistantMessage->content = 'assistant response';

        $chat = new Chat();
        $chat->setRelation('messages', new Collection([$assistantMessage]));

        $this->expectException(ModelNotFoundException::class);
        $chat->getFirstUserMessage();
    }

    public function test_get_first_assistant_message_uses_loaded_relation(): void
    {
        $userMessage = new Message();
        $userMessage->role = 'user';
        $userMessage->content = 'user question';

        $assistantMessage = new Message();
        $assistantMessage->role = 'assistant';
        $assistantMessage->content = 'assistant response';

        $chat = new Chat();
        $chat->setRelation('messages', new Collection([$userMessage, $assistantMessage]));

        $result = $chat->getFirstAssistantMessage();

        $this->assertSame('assistant', $result->role);
        $this->assertSame('assistant response', $result->content);
    }

    public function test_get_first_assistant_message_throws_when_no_assistant_message_in_loaded_relation(): void
    {
        $userMessage = new Message();
        $userMessage->role = 'user';
        $userMessage->content = 'user question';

        $chat = new Chat();
        $chat->setRelation('messages', new Collection([$userMessage]));

        $this->expectException(ModelNotFoundException::class);
        $chat->getFirstAssistantMessage();
    }

    public function test_get_last_user_message_uses_loaded_relation(): void
    {
        $userMessage1 = new Message();
        $userMessage1->role = 'user';
        $userMessage1->content = 'first question';

        $userMessage2 = new Message();
        $userMessage2->role = 'user';
        $userMessage2->content = 'second question';

        $chat = new Chat();
        $chat->setRelation('messages', new Collection([$userMessage1, $userMessage2]));

        $result = $chat->getLastUserMessage();

        $this->assertSame('second question', $result->content);
    }

    public function test_get_last_assistant_message_uses_loaded_relation(): void
    {
        $assistantMessage1 = new Message();
        $assistantMessage1->role = 'assistant';
        $assistantMessage1->content = 'first response';

        $assistantMessage2 = new Message();
        $assistantMessage2->role = 'assistant';
        $assistantMessage2->content = 'second response';

        $chat = new Chat();
        $chat->setRelation('messages', new Collection([$assistantMessage1, $assistantMessage2]));

        $result = $chat->getLastAssistantMessage();

        $this->assertSame('second response', $result->content);
    }

    public function test_get_history_uses_loaded_relation(): void
    {
        $userMessage = $this->createMock(Message::class);
        $userMessage->method('__get')->willReturnCallback(static fn (string $key) => match ($key) {
            'role' => 'user',
            'content' => 'question',
            'created_at' => Carbon::parse('2024-01-01 00:00:00'),
            default => null,
        });

        $assistantMessage = $this->createMock(Message::class);
        $assistantMessage->method('__get')->willReturnCallback(static fn (string $key) => match ($key) {
            'role' => 'assistant',
            'content' => 'response',
            'created_at' => Carbon::parse('2024-01-01 00:00:01'),
            default => null,
        });

        $chat = new Chat();
        $chat->setRelation('messages', new Collection([$userMessage, $assistantMessage]));

        $history = $chat->getHistory();

        $this->assertCount(2, $history);
        $this->assertSame('user', $history[0]['role']);
        $this->assertSame('question', $history[0]['content']);
        $this->assertSame('assistant', $history[1]['role']);
        $this->assertSame('response', $history[1]['content']);
    }

    public function test_is_block_uses_loaded_relation(): void
    {
        $message = $this->createMock(Message::class);
        $message->expects($this->once())
            ->method('isBlock')
            ->willReturn(true);

        $chat = new Chat();
        $chat->setRelation('messages', new Collection([$message]));

        $this->assertTrue($chat->isBlock());
    }
}
