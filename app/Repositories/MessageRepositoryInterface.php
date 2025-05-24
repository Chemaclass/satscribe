<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\PromptInput;
use App\Enums\PromptPersona;
use App\Models\Message;

interface MessageRepositoryInterface
{
    public function findAssistantMessage(PromptInput $input, PromptPersona $persona, string $question): ?Message;
}
