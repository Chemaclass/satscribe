<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Data\Chat;

use Modules\Shared\Domain\Enum\Chat\PromptType;

final readonly class PromptInput
{
    public function __construct(
        public PromptType $type,
        public string $text,
    ) {
    }

    /**
     * A block hash and a txid are both 64 hex characters; a height is digits.
     * Nothing else is a blockchain identifier, and the text ends up in the path
     * of an outbound Blockstream URL, so anything else is refused here rather
     * than being passed on as though it were a txid.
     */
    public static function isValid(string|int $input): bool
    {
        return is_numeric($input) || preg_match('/^[a-f0-9]{64}$/i', (string) $input) === 1;
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

    public function isTransaction(): bool
    {
        return $this->type === PromptType::Transaction;
    }
}
