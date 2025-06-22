<?php

declare(strict_types=1);

namespace Modules\Chat\Domain;

use Modules\Chat\Domain\Data\CreateChatActionResult;
use Modules\Shared\Domain\Data\Chat\PromptInput;
use Modules\Shared\Domain\Enum\Chat\PromptPersona;

interface CreateChatActionInterface
{
    public function execute(
        PromptInput $input,
        PromptPersona $persona,
        string $question,
        bool $refreshEnabled = false,
        bool $isPrivate = false,
    ): CreateChatActionResult;
}
