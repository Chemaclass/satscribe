<?php

declare(strict_types=1);

namespace Tests\Unit\Chat;

use Modules\Chat\Domain\Data\QuestionPlaceholder;
use Tests\TestCase;

final class QuestionPlaceholderTest extends TestCase
{
    public function test_rand_returns_a_string(): void
    {
        $result = QuestionPlaceholder::rand();

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function test_for_block_returns_three_prompts(): void
    {
        $result = QuestionPlaceholder::forBlock();

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
    }

    public function test_for_block_returns_strings(): void
    {
        $result = QuestionPlaceholder::forBlock();

        foreach ($result as $prompt) {
            $this->assertIsString($prompt);
            $this->assertNotEmpty($prompt);
        }
    }

    public function test_for_tx_returns_three_prompts(): void
    {
        $result = QuestionPlaceholder::forTx();

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
    }

    public function test_for_tx_returns_strings(): void
    {
        $result = QuestionPlaceholder::forTx();

        foreach ($result as $prompt) {
            $this->assertIsString($prompt);
            $this->assertNotEmpty($prompt);
        }
    }

    public function test_grouped_prompts_returns_array_by_category(): void
    {
        $result = QuestionPlaceholder::groupedPrompts();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('transaction', $result);
        $this->assertArrayHasKey('block', $result);
        $this->assertArrayHasKey('both', $result);
    }

    public function test_grouped_prompts_returns_two_per_category(): void
    {
        $result = QuestionPlaceholder::groupedPrompts();

        foreach ($result as $category => $prompts) {
            $this->assertCount(2, $prompts, "Category {$category} should have 2 prompts");
        }
    }

    public function test_sample_questions_constant_has_expected_structure(): void
    {
        $questions = QuestionPlaceholder::SAMPLE_QUESTIONS;

        $this->assertArrayHasKey('transaction', $questions);
        $this->assertArrayHasKey('block', $questions);
        $this->assertArrayHasKey('both', $questions);

        $this->assertNotEmpty($questions['transaction']);
        $this->assertNotEmpty($questions['block']);
        $this->assertNotEmpty($questions['both']);
    }
}
