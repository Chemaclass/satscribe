<?php

declare(strict_types=1);

namespace App\Enums;

enum PromptPersona: string
{
    case Educator = 'educator';
    case Developer = 'developer';
    case Storyteller = 'storyteller';

    const DEFAULT = self::Educator->value;

    public function label(): string
    {
        return match ($this) {
            self::Educator => "Educator – Teach Bitcoin to beginners with simple examples",
            self::Developer => "Developer – Explain Bitcoin internals with technical precision",
            self::Storyteller => "Storyteller – Share Bitcoin insights through stories and narratives",
        };
    }

    public static function options(): array
    {
        return array_map(
            fn(self $persona) => [
                'value' => $persona->value,
                'label' => $persona->label(),
                'enabled' => $persona->value === self::DEFAULT,
            ],
            self::cases()
        );
    }

    public function systemPrompt(): string
    {
        $persona = match ($this) {
            self::Educator => 'You are a Bitcoin educator. Your mission is to make Bitcoin understandable to total beginners. Use analogies, simple examples, and avoid technical jargon.',
            self::Developer => 'You are a Bitcoin core developer and educator. Focus on technical details like script types, transaction structures, and protocol behavior. Assume your audience understands Bitcoin basics but not internals.',
            self::Storyteller => 'You are a Bitcoin storyteller. Use history, anecdotes, and human motivations to explain Bitcoin topics in an engaging, memorable way.',
        };

        return <<<TEXT
$persona
You will receive structured blockchain data for CONTEXT ONLY.
Do NOT mechanically list or repeat back the data.
Your role is to craft an insightful, persona-aligned response.
Prioritize clarity, brevity, and meaningful key takeaways over exhaustive details.
TEXT;
    }

    public function maxTokens(): int
    {
        return match ($this) {
            self::Educator,
            self::Developer,
            self::Storyteller => 700,
        };
    }
}
