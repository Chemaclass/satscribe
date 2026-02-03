<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Chat;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Chat\Domain\ViewModel\HistoryChatItem;
use Tests\TestCase;

final class HistoryChatItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_from_chat_creates_item_for_transaction(): void
    {
        $chat = Chat::create([
            'ulid' => 'test-ulid',
            'tracking_id' => 'owner-tracking',
            'is_public' => true,
            'is_shared' => false,
        ]);

        Message::create([
            'chat_id' => $chat->id,
            'role' => 'user',
            'content' => 'What is this transaction?',
            'meta' => [
                'type' => 'transaction',
                'input' => 'abc123txid',
                'persona' => 'educator',
            ],
        ]);

        Message::create([
            'chat_id' => $chat->id,
            'role' => 'assistant',
            'content' => 'This is a transaction response.',
            'meta' => [
                'type' => 'transaction',
                'input' => 'abc123txid',
                'raw_data' => ['txid' => 'abc123txid'],
            ],
        ]);

        $item = HistoryChatItem::fromChat($chat->fresh(), 'owner-tracking');

        $this->assertSame('test-ulid', $item->ulid);
        $this->assertTrue($item->isPublic);
        $this->assertFalse($item->isShared);
        $this->assertTrue($item->owned);
        $this->assertSame('transaction', $item->type);
        $this->assertSame('abc123txid', $item->input);
        $this->assertSame('What is this transaction?', $item->userMessage);
        $this->assertSame('This is a transaction response.', $item->assistantMessage);
        $this->assertFalse($item->isBlock);
        $this->assertSame('https://mempool.space/tx/abc123txid', $item->mempoolUrl);
    }

    public function test_from_chat_creates_item_for_block(): void
    {
        $chat = Chat::create([
            'ulid' => 'test-ulid',
            'tracking_id' => 'owner-tracking',
            'is_public' => false,
            'is_shared' => true,
        ]);

        Message::create([
            'chat_id' => $chat->id,
            'role' => 'user',
            'content' => 'Tell me about this block',
            'meta' => [
                'type' => 'block',
                'input' => '800000',
                'persona' => 'storyteller',
            ],
        ]);

        Message::create([
            'chat_id' => $chat->id,
            'role' => 'assistant',
            'content' => 'This block was mined.',
            'meta' => [
                'type' => 'block',
                'input' => '800000',
                'raw_data' => ['hash' => 'blockhash123'],
            ],
        ]);

        $item = HistoryChatItem::fromChat($chat->fresh(), 'owner-tracking');

        $this->assertFalse($item->isPublic);
        $this->assertTrue($item->isShared);
        $this->assertTrue($item->isBlock);
        $this->assertSame('https://mempool.space/block/blockhash123', $item->mempoolUrl);
    }

    public function test_from_chat_marks_as_not_owned_for_different_tracking_id(): void
    {
        $chat = Chat::create([
            'ulid' => 'test-ulid',
            'tracking_id' => 'owner-tracking',
            'is_public' => true,
            'is_shared' => false,
        ]);

        Message::create([
            'chat_id' => $chat->id,
            'role' => 'user',
            'content' => 'Question',
            'meta' => ['type' => 'transaction', 'input' => 'abc123', 'persona' => 'educator'],
        ]);

        Message::create([
            'chat_id' => $chat->id,
            'role' => 'assistant',
            'content' => 'Response',
            'meta' => ['type' => 'transaction', 'input' => 'abc123'],
        ]);

        $item = HistoryChatItem::fromChat($chat->fresh(), 'different-tracking');

        $this->assertFalse($item->owned);
    }

    public function test_from_chat_uses_input_when_no_raw_data_hash(): void
    {
        $chat = Chat::create([
            'ulid' => 'test-ulid',
            'tracking_id' => 'owner-tracking',
            'is_public' => true,
            'is_shared' => false,
        ]);

        Message::create([
            'chat_id' => $chat->id,
            'role' => 'user',
            'content' => 'Question',
            'meta' => ['type' => 'block', 'input' => '800000', 'persona' => 'educator'],
        ]);

        Message::create([
            'chat_id' => $chat->id,
            'role' => 'assistant',
            'content' => 'Response',
            'meta' => ['type' => 'block', 'input' => '800000', 'raw_data' => []],
        ]);

        $item = HistoryChatItem::fromChat($chat->fresh(), 'owner-tracking');

        $this->assertSame('https://mempool.space/block/800000', $item->mempoolUrl);
    }
}
