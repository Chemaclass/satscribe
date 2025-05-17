<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Data\PromptInput;
use App\Enums\PromptPersona;
use App\Models\Message;

final readonly class MessageRepository
{
    public function __construct(
        private string $ip,
    ) {
    }

    public function findByCriteria(
        PromptInput $input,
        PromptPersona $persona,
    ): ?Message {
        return Message::query()
            ->where('creator_ip', $this->ip)
            ->where('role', 'assistant')
            ->whereJsonContains('meta->type', $input->type->value)
            ->whereJsonContains('meta->input', $input->text)
            ->whereJsonContains('meta->persona', $persona->value)
            ->first();
    }
}
