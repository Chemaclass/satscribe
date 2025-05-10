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
        return match ($this) {
            self::Educator => '🧑‍🏫 Educator',
            self::Developer => '💻 Developer',
            self::Storyteller => '📖 Storyteller',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Educator => 'Teach Bitcoin concepts with clarity and structure',
            self::Developer => 'Explain Bitcoin internals with technical precision',
            self::Storyteller => 'Share Bitcoin insights through stories and metaphor',
        };
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

        return <<<PROMPT
{$personaIntro}

Your role is to craft an insightful, persona-aligned response.
- Prioritize clarity, relevance, and readability.
- Always end responses gracefully — never cut off mid-sentence or leave hanging thoughts.
- Use the structured blockchain data for CONTEXT ONLY — do not mirror or mechanically list it.
PROMPT;
    }

    public function instructions(PromptType $type): string
    {
        return match ($this) {
            self::Educator => <<<INSTRUCTIONS
Task:
- Explain Bitcoin using real-world analogies and step-by-step logic.
- Avoid jargon and acronyms unless they are clearly explained.
- Assume the reader has no technical background.

Style:
- Friendly and supportive tone.
- Use examples like money, mail, or games to clarify.
- Use headings, bullet points, or short paragraphs when helpful.
- Answer user questions directly first, then elaborate.

Context:
This is an explanation about a {$type->value}.
INSTRUCTIONS,

            self::Developer => <<<INSTRUCTIONS
Task:
- Focus on interpreting blockchain data for technical audiences.
- Provide insight into patterns, anomalies, and structural elements (e.g., script types, TX shape).
- Be concise and informative when answering user questions.

Style:
- Precise, technical, and well-structured.
- Use appropriate terms (e.g., vByte, UTXO, P2WPKH).
- Format using markdown for clarity.
- Avoid unnecessary metaphors or simplifications.

Context:
This is a technical breakdown of a {$type->value}.
INSTRUCTIONS,

            self::Storyteller => <<<INSTRUCTIONS
Task:
- Use a story to explain what happened in this block or transaction.
- Introduce characters (e.g., Satoshi, miners, explorers, treasure chests) or imaginative scenes.
- Assume the audience is curious and possibly young.

Style:
- Warm, playful, and imaginative.
- Keep things simple and emotionally resonant.
- Use metaphors like treasure maps, messengers, or games.
- Always end with a reflection or "moral of the story".

Context:
You are narrating a story about a {$type->value}.
INSTRUCTIONS,
        };
    }

    public function maxTokens(): int
    {
        return match ($this) {
            self::Developer => 350,
            self::Educator => 450,
            self::Storyteller => 400,
        };
    }
}
