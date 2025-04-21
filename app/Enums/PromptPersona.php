<?php

declare(strict_types=1);

namespace App\Enums;

enum PromptPersona: string
{
    case Educator = 'educator';
    case Developer = 'developer';
    case Storyteller = 'storyteller';

    public function label(): string
    {
        return match ($this) {
            self::Educator => "Educator – Explain it like I'm new",
            self::Developer => 'Developer – Focus on scripts & code',
            self::Storyteller => 'Storyteller – Add narrative and context',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn(self $persona) => ['value' => $persona->value, 'label' => $persona->label()],
            self::cases()
        );
    }
}
