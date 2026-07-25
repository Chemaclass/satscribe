<?php

declare(strict_types=1);

namespace Modules\OpenAI\Domain\Data;

/**
 * A single model offered by a provider.
 *
 * The three tiers are distinct: `free` costs nothing at the provider's free
 * tier but still needs a key; `premium` is served with this deployment's own
 * key and paid for by the visitor in sats; anything else needs the visitor to
 * bring their own key.
 */
final readonly class AiModel
{
    public function __construct(
        public string $id,
        public string $label,
        public bool $free = false,
        public bool $premium = false,
    ) {
    }

    /**
     * @return array{id: string, label: string, free: bool, premium: bool}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'free' => $this->free,
            'premium' => $this->premium,
        ];
    }
}
