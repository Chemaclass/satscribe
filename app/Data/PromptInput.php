<?php
declare(strict_types=1);

namespace App\Data;

use App\Enums\PromptType;

final readonly class PromptInput
{
    public function __construct(
        public PromptType $type,
        public string $text,
    ) {
    }

    public static function fromRaw(string|int $input): self
    {
        if (is_numeric($input)
            || preg_match('/^0{8,}[a-f0-9]{56}$/i', $input)
        ) {
            return new self(PromptType::Block, (string) $input);
        }

        return new self(PromptType::Transaction, $input);
    }

    public function isBlock(): bool
    {
        return $this->type === PromptType::Block;
    }
}
