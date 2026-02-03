<?php

declare(strict_types=1);

namespace Tests\Unit\Chat;

use App\Models\Message;
use PHPUnit\Framework\TestCase;

final class MessageModelTest extends TestCase
{
    public function test_is_block_returns_true_for_block_type(): void
    {
        $message = new Message();
        $message->meta = ['type' => 'block'];

        $this->assertTrue($message->isBlock());
    }

    public function test_is_block_returns_false_for_transaction_type(): void
    {
        $message = new Message();
        $message->meta = ['type' => 'transaction'];

        $this->assertFalse($message->isBlock());
    }

    public function test_is_block_returns_false_when_type_is_null(): void
    {
        $message = new Message();
        $message->meta = [];

        $this->assertFalse($message->isBlock());
    }

    public function test_get_type_attribute(): void
    {
        $message = new Message();
        $message->meta = ['type' => 'transaction'];

        $this->assertSame('transaction', $message->type);
    }

    public function test_get_type_attribute_returns_null_when_missing(): void
    {
        $message = new Message();
        $message->meta = [];

        $this->assertNull($message->type);
    }

    public function test_get_input_attribute(): void
    {
        $message = new Message();
        $message->meta = ['input' => 'abc123'];

        $this->assertSame('abc123', $message->input);
    }

    public function test_get_persona_attribute(): void
    {
        $message = new Message();
        $message->meta = ['persona' => 'educator'];

        $this->assertSame('educator', $message->persona);
    }

    public function test_get_raw_data_attribute(): void
    {
        $rawData = ['txid' => 'abc', 'fee' => 1000];
        $message = new Message();
        $message->meta = ['raw_data' => $rawData];

        $this->assertSame($rawData, $message->rawData);
    }

    public function test_get_raw_data_returns_null_when_missing(): void
    {
        $message = new Message();
        $message->meta = [];

        $this->assertNull($message->rawData);
    }

    public function test_get_force_refresh_attribute(): void
    {
        $message = new Message();
        $message->meta = ['force_refresh' => true];

        $this->assertTrue($message->force_refresh);
    }

    public function test_get_force_refresh_returns_false_when_missing(): void
    {
        $message = new Message();
        $message->meta = [];

        $this->assertFalse($message->force_refresh);
    }

    public function test_get_question_attribute(): void
    {
        $message = new Message();
        $message->meta = ['question' => 'What is Bitcoin?'];

        $this->assertSame('What is Bitcoin?', $message->question);
    }

    public function test_get_question_returns_null_when_missing(): void
    {
        $message = new Message();
        $message->meta = [];

        $this->assertNull($message->question);
    }
}
