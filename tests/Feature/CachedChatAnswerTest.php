<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Chat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Chat\Infrastructure\Repository\ChatRepository;
use Modules\Shared\Domain\Data\Chat\PromptInput;
use Modules\Shared\Domain\Enum\Chat\PromptPersona;
use Modules\Shared\Domain\Enum\Chat\PromptType;
use Tests\TestCase;

/**
 * CreateChatAction returns a matching chat straight from this lookup, so a chat
 * whose answer is blank — the rows saved before the empty-response guard
 * existed — would be replayed as a finished result.
 */
final class CachedChatAnswerTest extends TestCase
{
    use RefreshDatabase;

    private const TXID = 'abc123txid';

    public function test_a_chat_with_a_blank_answer_is_not_reused(): void
    {
        $this->chatAnswering('');

        self::assertNull($this->find());
    }

    public function test_a_chat_with_a_whitespace_answer_is_not_reused(): void
    {
        $this->chatAnswering("  \n ");

        self::assertNull($this->find());
    }

    public function test_a_chat_with_a_real_answer_is_reused(): void
    {
        $this->chatAnswering('A real answer.');

        self::assertNotNull($this->find());
    }

    public function test_the_newest_usable_chat_wins_over_an_older_blank_one(): void
    {
        $this->chatAnswering('');
        $this->chatAnswering('A real answer.');

        $found = $this->find();

        self::assertNotNull($found);
        self::assertSame('A real answer.', $found->getLastAssistantMessage()->content);
    }

    private function find(): ?Chat
    {
        return (new ChatRepository(10, 'owner'))->findByCriteria(
            new PromptInput(PromptType::Transaction, self::TXID),
            PromptPersona::Educator,
            'What is this?',
        );
    }

    private function chatAnswering(string $answer): Chat
    {
        $chat = Chat::create([
            'title' => 'Test Chat',
            'tracking_id' => 'owner',
            'is_public' => false,
            'is_shared' => false,
        ]);

        $meta = ['type' => 'transaction', 'input' => self::TXID, 'persona' => 'educator'];

        $chat->addUserMessage('What is this?', $meta);
        $chat->addAssistantMessage($answer, $meta + ['question' => 'What is this?']);

        return $chat;
    }
}
