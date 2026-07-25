<?php

declare(strict_types=1);

namespace Modules\Chat\Domain;

use App\Models\Chat;
use Generator;

/**
 * @phpstan-import-type TStreamEvent from CreateChatStreamActionInterface
 */
interface AddMessageStreamActionInterface
{
    /**
     * @return Generator<TStreamEvent>
     */
    public function execute(Chat $chat, string $message): Generator;
}
