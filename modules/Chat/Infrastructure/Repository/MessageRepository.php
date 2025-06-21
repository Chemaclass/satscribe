<?php
declare(strict_types=1);
namespace Modules\Chat\Infrastructure\Repository;
use App\Models\Message;
use Modules\Chat\Domain\Data\PromptInput;
use Modules\Chat\Domain\Enum\PromptPersona;
use Modules\Chat\Domain\Repository\MessageRepositoryInterface;

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
