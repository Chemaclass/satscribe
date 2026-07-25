<?php

declare(strict_types=1);

namespace Modules\OpenAI\Domain\Data;

/**
 * A single model offered by a provider. `free` means "costs the caller
 * nothing at the provider's free tier", not "usable without a key".
 */
final readonly class AiModel
{
    public function __construct(
        public string $id,
        public string $label,
        public bool $free = false,
    ) {
    }

    /**
     * @return array{id: string, label: string, free: bool}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'free' => $this->free,
        ];
    }
}
