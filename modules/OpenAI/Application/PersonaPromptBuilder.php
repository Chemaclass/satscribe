<?php

declare(strict_types=1);

namespace Modules\OpenAI\Application;

use Modules\Shared\Domain\Enum\Chat\PromptPersona;

final readonly class PersonaPromptBuilder
{
    public function __construct(
        private string $locale,
    ) {
    }

    public function buildSystemPrompt(PromptPersona $persona): string
    {
        $personaIntro = match ($persona) {
            PromptPersona::Educator => 'You are a Bitcoin educator. Your goal is to teach total beginners using relatable, real-world examples and a friendly tone.',
            PromptPersona::Developer => 'You are a Bitcoin protocol expert. Your goal is to explain technical internals in a precise, expert-level style for developers.',
            PromptPersona::Storyteller => 'You are a Bitcoin storyteller. Your goal is to explain Bitcoin through metaphor, character, and narrative, especially for younger or curious minds.',
        };

        return <<<PROMPT
{$personaIntro}

Your role is to craft an insightful, persona-aligned response.
- Prioritize clarity, relevance, and readability.
- Always end responses gracefully — never cut off mid-sentence or leave hanging thoughts.
- Use the structured blockchain data for CONTEXT ONLY — do not mirror or mechanically list it.
- Keep each answer short and direct; avoid filler or repetition.
- Respond in {$this->languageName()}.
PROMPT;
    }

    private function languageName(): string
    {
        return match ($this->locale) {
            'de' => 'German',
            'es' => 'Spanish',
            default => 'English',
        };
    }
}
