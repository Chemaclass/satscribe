<?php
declare(strict_types=1);

namespace Modules\Shared\Domain\Enum\Chat;

enum PromptType: string
{
    case Block = 'block';
    case Transaction = 'transaction';

    public static function fromInput(string $input): self
    {
        if (is_numeric($input) || preg_match('/^0{8,}[a-f0-9]{56}$/i', $input)) {
            return self::Block;
        }

        return self::Transaction;
    }
}
