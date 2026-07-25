<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Chat\Infrastructure\Repository\ChatRepository;
use Modules\Shared\Domain\Data\Blockchain\TransactionData;
use Modules\Shared\Domain\Data\Chat\PromptInput;
use Modules\Shared\Domain\Enum\Chat\PromptPersona;
use Modules\Shared\Domain\Enum\Chat\PromptType;
use Tests\TestCase;

/**
 * A transaction that was unconfirmed when it was answered is marked
 * force_refresh so the cached chat is not replayed once it has been mined.
 * CreateChatAction reads that flag off the chat, so it has to survive the round
 * trip through the stored messages.
 */
final class ForceRefreshTest extends TestCase
{
    use RefreshDatabase;

    private const TXID = 'abc123txid';

    public function test_an_unconfirmed_transaction_marks_the_chat_for_refresh(): void
    {
        $chat = $this->createChatFor(confirmed: false);

        self::assertTrue($chat->refresh()->force_refresh);
    }

    public function test_a_confirmed_transaction_does_not(): void
    {
        $chat = $this->createChatFor(confirmed: true);

        self::assertFalse($chat->refresh()->force_refresh);
    }

    public function test_a_block_does_not(): void
    {
        $repository = new ChatRepository(10, 'owner');

        $chat = $repository->createChat(
            new PromptInput(PromptType::Block, '210000'),
            'answer',
            new TransactionData(txid: self::TXID, confirmed: false),
            PromptPersona::Educator,
            'What is this?',
            false,
        );

        self::assertFalse($chat->refresh()->force_refresh);
    }

    private function createChatFor(bool $confirmed): \App\Models\Chat
    {
        $repository = new ChatRepository(10, 'owner');

        return $repository->createChat(
            new PromptInput(PromptType::Transaction, self::TXID),
            'answer',
            new TransactionData(txid: self::TXID, confirmed: $confirmed),
            PromptPersona::Educator,
            'What is this?',
            false,
        );
    }
}
