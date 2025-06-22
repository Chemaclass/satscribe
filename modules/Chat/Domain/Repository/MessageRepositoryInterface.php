<?php

declare(strict_types=1);

namespace Modules\Chat\Domain\Repository;

use App\Models\Message;
use Modules\Shared\Domain\Data\Chat\PromptInput;
use Modules\Shared\Domain\Enum\Chat\PromptPersona;

interface MessageRepositoryInterface
{
    public function findAssistantMessage(PromptInput $input, PromptPersona $persona, string $question): ?Message;
}
