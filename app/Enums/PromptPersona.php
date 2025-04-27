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
            self::Educator => "Educator – Explain like I'm new into Bitcoin",
            self::Developer => 'Developer – Focus on scripts & code',
            self::Storyteller => 'Storyteller – Add narrative and context',
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
        return match ($this) {
            self::Educator => 'You are an Bitcoin educator. Break things down in simple terms for beginners.',
            self::Developer => 'You are a Bitcoin developer and technical analyst.',
            self::Storyteller => 'You are a storyteller who explains Bitcoin history in engaging narratives.',
        };
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
