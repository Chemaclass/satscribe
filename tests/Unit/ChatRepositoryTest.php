<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Chat;
use App\Models\Message;
use Modules\Chat\Infrastructure\Repository\ChatRepository;
use Modules\Shared\Domain\Enum\Chat\PromptType;
use PHPUnit\Framework\TestCase;

final class ChatRepositoryTest extends TestCase
{
    public function test_add_message_to_chat_preserves_input_metadata(): void
    {
        $firstMsg = new Message();
        $firstMsg->meta = [
            'type' => PromptType::Transaction->value,
            'input' => 'abc',
            'persona' => 'storyteller',
        ];

        $chat = $this->getMockBuilder(Chat::class)
            ->onlyMethods([
                'getFirstUserMessage',
                'getFirstAssistantMessage',
                'addUserMessage',
                'addAssistantMessage',
            ])
            ->getMock();

        $chat->method('getFirstUserMessage')->willReturn($firstMsg);
        $chat->method('getFirstAssistantMessage')->willReturn($firstMsg);

        $capturedMeta = null;
        $chat->expects($this->once())
            ->method('addUserMessage')
            ->with(
                'new question',
                $this->callback(static function (array $meta) use (&$capturedMeta): bool {
                    $capturedMeta = $meta;
                    return true;
                }),
            );

        $chat->expects($this->once())
            ->method('addAssistantMessage')
            ->with('answer', $this->anything());

        $repo = new ChatRepository(perPage: 10, trackingId: 't');
        $repo->addMessageToChat($chat, 'new question', 'answer');

        $this->assertSame('abc', $capturedMeta['input']);
    }
}
