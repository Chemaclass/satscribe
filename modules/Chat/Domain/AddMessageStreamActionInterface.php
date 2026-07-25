<?php

declare(strict_types=1);

namespace Modules\Chat\Domain;

use App\Models\Chat;
use Generator;
use Modules\OpenAI\Domain\Data\ModelSelection;

/**
 * @phpstan-import-type TStreamEvent from CreateChatStreamActionInterface
 */
interface AddMessageStreamActionInterface
{
    /**
     * @param ?ModelSelection $selection null keeps the configured default model
     *
     * @return Generator<TStreamEvent>
     */
    public function execute(Chat $chat, string $message, ?ModelSelection $selection = null): Generator;
}
