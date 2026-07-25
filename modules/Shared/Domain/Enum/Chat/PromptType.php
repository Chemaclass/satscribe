<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Enum\Chat;

enum PromptType: string
{
    case Block = 'block';
    case Transaction = 'transaction';
}
