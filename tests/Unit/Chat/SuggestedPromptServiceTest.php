<?php

declare(strict_types=1);

namespace Tests\Unit\Chat;

use Modules\Chat\Application\SuggestedPromptService;
use Modules\Chat\Domain\Data\QuestionPlaceholder;
use Tests\TestCase;

final class SuggestedPromptServiceTest extends TestCase
{
    public function test_get_grouped_prompts_returns_prompts_for_null_chat(): void
    {
        $service = new SuggestedPromptService();

        $result = $service->getGroupedPrompts(null);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        // Should have same categories as SAMPLE_QUESTIONS
        $this->assertSame(
            array_keys(QuestionPlaceholder::SAMPLE_QUESTIONS),
            array_keys($result),
        );
    }

    public function test_get_grouped_prompts_limits_to_two_per_category(): void
    {
        $service = new SuggestedPromptService();

        $result = $service->getGroupedPrompts(null);

        foreach ($result as $category => $prompts) {
            $this->assertLessThanOrEqual(2, $prompts->count(), "Category {$category} has more than 2 prompts");
        }
    }

    public function test_get_grouped_prompts_returns_collections(): void
    {
        $service = new SuggestedPromptService();

        $result = $service->getGroupedPrompts(null);

        foreach ($result as $prompts) {
            $this->assertInstanceOf(\Illuminate\Support\Collection::class, $prompts);
        }
    }

    public function test_get_grouped_prompts_returns_translated_prompts(): void
    {
        $service = new SuggestedPromptService();

        $result = $service->getGroupedPrompts(null);

        // Each prompt should be a string (translated)
        foreach ($result as $prompts) {
            foreach ($prompts as $prompt) {
                $this->assertIsString($prompt);
                $this->assertNotEmpty($prompt);
            }
        }
    }
}
