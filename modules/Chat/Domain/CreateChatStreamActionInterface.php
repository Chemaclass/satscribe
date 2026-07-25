<?php

declare(strict_types=1);

namespace Modules\Chat\Domain;

use Generator;
use Modules\Shared\Domain\Data\Chat\PromptInput;
use Modules\Shared\Domain\Enum\Chat\PromptPersona;

/**
 * Owns the shape of the server-sent events emitted by the chat streaming actions.
 * Import these aliases rather than respelling the shape.
 *
 * @phpstan-type TStreamEvent array{type: string, data: mixed}
 * @phpstan-type TStreamDoneEvent array{type: string, data: array<string, mixed>}
 */
interface CreateChatStreamActionInterface
{
    /**
     * @return Generator<TStreamEvent>
     */
    public function execute(
        PromptInput $input,
        PromptPersona $persona,
        string $question,
        bool $isPublic = false,
    ): Generator;
}
