<?php

declare(strict_types=1);

namespace App\Enums;

enum PromptType: string
{
    case Block = 'block';
    case Transaction = 'transaction';

    public static function fromInput(string $input): self
    {
        return is_numeric($input) ? self::Block : self::Transaction;
    }
}
