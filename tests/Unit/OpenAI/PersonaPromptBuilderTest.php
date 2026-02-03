<?php

declare(strict_types=1);

namespace Tests\Unit\OpenAI;

use Modules\OpenAI\Application\PersonaPromptBuilder;
use Modules\Shared\Domain\Enum\Chat\PromptPersona;
use PHPUnit\Framework\TestCase;

final class PersonaPromptBuilderTest extends TestCase
{
    public function test_build_system_prompt_for_educator(): void
    {
        $builder = new PersonaPromptBuilder('en');

        $prompt = $builder->buildSystemPrompt(PromptPersona::Educator);

        $this->assertStringContainsString('Bitcoin educator', $prompt);
        $this->assertStringContainsString('teach total beginners', $prompt);
        $this->assertStringContainsString('English', $prompt);
    }

    public function test_build_system_prompt_for_developer(): void
    {
        $builder = new PersonaPromptBuilder('en');

        $prompt = $builder->buildSystemPrompt(PromptPersona::Developer);

        $this->assertStringContainsString('Bitcoin protocol expert', $prompt);
        $this->assertStringContainsString('technical internals', $prompt);
        $this->assertStringContainsString('developers', $prompt);
    }

    public function test_build_system_prompt_for_storyteller(): void
    {
        $builder = new PersonaPromptBuilder('en');

        $prompt = $builder->buildSystemPrompt(PromptPersona::Storyteller);

        $this->assertStringContainsString('Bitcoin storyteller', $prompt);
        $this->assertStringContainsString('metaphor', $prompt);
        $this->assertStringContainsString('narrative', $prompt);
    }

    public function test_build_system_prompt_in_german(): void
    {
        $builder = new PersonaPromptBuilder('de');

        $prompt = $builder->buildSystemPrompt(PromptPersona::Educator);

        $this->assertStringContainsString('German', $prompt);
    }

    public function test_build_system_prompt_in_spanish(): void
    {
        $builder = new PersonaPromptBuilder('es');

        $prompt = $builder->buildSystemPrompt(PromptPersona::Educator);

        $this->assertStringContainsString('Spanish', $prompt);
    }

    public function test_build_system_prompt_defaults_to_english_for_unknown_locale(): void
    {
        $builder = new PersonaPromptBuilder('fr');

        $prompt = $builder->buildSystemPrompt(PromptPersona::Educator);

        $this->assertStringContainsString('English', $prompt);
    }

    public function test_prompt_contains_common_guidelines(): void
    {
        $builder = new PersonaPromptBuilder('en');

        $prompt = $builder->buildSystemPrompt(PromptPersona::Educator);

        $this->assertStringContainsString('clarity', $prompt);
        $this->assertStringContainsString('readability', $prompt);
        $this->assertStringContainsString('Avoid using latex', $prompt);
    }
}
