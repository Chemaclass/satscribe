<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Data\PromptInput;
use App\Enums\PromptPersona;
use App\Models\Message;
use App\Repositories\MessageRepositoryInterface;

final readonly class MessageRepository implements MessageRepositoryInterface
{
    public function findAssistantMessage(PromptInput $input, PromptPersona $persona, string $question): ?Message
    {
        return Message::query()
            ->where('role', 'assistant')
            ->whereJsonContains('meta->type', $input->type->value)
            ->whereJsonContains('meta->input', $input->text)
            ->whereJsonContains('meta->persona', $persona->value)
            ->whereJsonContains('meta->question', $question)
            ->first();
    }
}
