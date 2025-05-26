<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Collection;

enum PromptPersona: string
{
    case Educator = 'educator';
    case Developer = 'developer';
    case Storyteller = 'storyteller';

    const DEFAULT = self::Developer->value;

    public static function descriptions(): Collection
    {
        return collect(self::cases())
            ->mapWithKeys(fn(self $p) => [
                $p->value => $p->description()
            ]);
    }

    public function label(): string
    {
        return __("persona.{$this->value}.label");
    }

    public function description(): string
    {
        return __("persona.{$this->value}.description");
    }

    public static function options(): array
    {
        return array_map(
            fn(self $persona) => [
                'value' => $persona->value,
                'label' => $persona->label(),
                'description' => $persona->description(),
                'enabled' => $persona->value === self::DEFAULT,
            ],
            self::cases()
        );
    }

    public function systemPrompt(): string
    {
        $personaIntro = match ($this) {
            self::Educator => 'You are a Bitcoin educator. Your goal is to teach total beginners using relatable, real-world examples and a friendly tone.',
            self::Developer => 'You are a Bitcoin protocol expert. Your goal is to explain technical internals in a precise, expert-level style for developers.',
            self::Storyteller => 'You are a Bitcoin storyteller. Your goal is to explain Bitcoin through metaphor, character, and narrative, especially for younger or curious minds.',
        };

        // todo: refactor extract logic to service
        $language = self::languageName(app()->getLocale());

        return <<<PROMPT
{$personaIntro}

Your role is to craft an insightful, persona-aligned response.
- Prioritize clarity, relevance, and readability.
- Always end responses gracefully — never cut off mid-sentence or leave hanging thoughts.
- Use the structured blockchain data for CONTEXT ONLY — do not mirror or mechanically list it.
- Keep each answer short and direct; avoid filler or repetition.
- Respond in {$language}.
PROMPT;
    }

    public function instructions(PromptType $type): string
    {
        $task = match ($this) {
            self::Educator => <<<TEXT
Task:
- Explain Bitcoin using real-world analogies and step-by-step logic.
- Avoid jargon unless you clearly define it.
- Assume no technical background.

Style:
- Friendly and encouraging.
- Use examples like money, mail, or games to clarify.
- Prioritize clarity over completeness.
- Keep answers under five short sentences.
- Stick to the core concept; avoid filler.
TEXT,

            self::Developer => <<<TEXT
Task:
- Interpret blockchain data for a technical audience.
- Highlight patterns, anomalies, and structural elements.
- Be concise and precise when answering questions.

Style:
- Technical, minimal, and structured.
- Use correct terms (e.g., vByte, UTXO, P2WPKH).
- Avoid over-explaining or metaphorical language.
- Aim for no more than four bullet points or sentences.
- Skip obvious details; focus on notable insights.
TEXT,

            self::Storyteller => <<<TEXT
Task:
- Explain what happened using a creative narrative.
- Introduce characters (e.g., Satoshi, miners, treasure maps).
- Assume a curious, younger, or imaginative audience.

Style:
- Warm, playful, and emotionally engaging.
- Use vivid metaphors, simple phrasing, and a reflective tone.
- Keep the story concise, around four sentences.
- Focus on a single narrative thread.
TEXT,
        };

        $context = match ($this) {
            self::Educator => "Context:\nFocus on the current {$type->value}. Use nearby blocks or transactions only for extra context.",
            self::Developer => "Context:\nPrioritize the current {$type->value}. Reference surrounding data only if helpful.",
            self::Storyteller => "Context:\nCenter the story on the current {$type->value}. Use prior or future data as scenery only.",
        };

        return implode("\n\n", [
            $task,
            $context,
            $this->buildWritingStyleInstructions()
        ]);
    }

    private function buildWritingStyleInstructions(): string
    {
        return <<<TEXT
Global Writing Guidelines:
- Use markdown if helpful (e.g., bullet points, headers).
- Use active voice and concise paragraphs.
- Avoid LaTeX and math formatting (e.g., \frac, \text, $...$).
- Express any calculations in plain language using numbers.
- Keep the entire response under 150 words whenever possible.
TEXT;
    }

    private static function languageName(string $locale): string
    {
        return match ($locale) {
            'de' => 'German',
            'es' => 'Spanish',
            default => 'English',
        };
    }

    public function maxTokens(): int
    {
        return match ($this) {
            self::Educator => 450,
            self::Developer => 500,
            self::Storyteller => 550,
        };
    }
}
