<?php

declare(strict_types=1);

namespace Modules\Chat\Domain;

use App\Models\Chat;
use Generator;

interface AddMessageStreamActionInterface
{
    /**
     * @return Generator<array{type: string, data: mixed}>
     */
    public function execute(Chat $chat, string $message): Generator;
}
