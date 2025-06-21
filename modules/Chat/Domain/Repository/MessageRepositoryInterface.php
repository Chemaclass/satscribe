<?php

declare(strict_types=1);

namespace Modules\Chat\Domain\Repository;

use App\Models\Message;
use Modules\Chat\Domain\Data\PromptInput;
use Modules\Chat\Domain\Enum\PromptPersona;

interface MessageRepositoryInterface
{
    public function findAssistantMessage(PromptInput $input, PromptPersona $persona, string $question): ?Message;
}
