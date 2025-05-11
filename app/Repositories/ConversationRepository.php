<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Data\BlockchainDataInterface;
use App\Data\PromptInput;
use App\Enums\PromptPersona;
use App\Enums\PromptType;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Pagination\Paginator;

final readonly class ConversationRepository
{
    public function __construct(
        private int $perPage
    ) {
    }

    /**
     * Find a conversation matching given details or return null.
     */
    public function findByCriteria(
        PromptInput $input,
        PromptPersona $persona,
        string $question = '',
    ): ?Conversation {
        // Find an existing conversation by input type, input, persona, and question (legacy compatibility)
        return Conversation::whereHas('messages', function ($q) use ($input, $persona, $question) {
            $q->where('role', 'user')
              ->where('content', $question)
              ->whereJsonContains('meta->type', $input->type->value)
              ->whereJsonContains('meta->input', $input->text)
              ->whereJsonContains('meta->persona', $persona->value);
        })->first();
    }

    /**
     * Create a conversation and attach user & assistant messages using the legacy pattern.
     */
    public function save(
        PromptInput $input,
        string $aiResponse,
        BlockchainDataInterface $blockchainData,
        PromptPersona $persona,
        string $question = ''
    ): Conversation {
        $raw = $blockchainData->toArray();

        $forceRefresh = $input->type->value === PromptType::Transaction->value
            && isset($raw['status']['confirmed'])
            && $raw['status']['confirmed'] === false;

        // Create conversation
        $conversation = Conversation::create([
            'title' => ucfirst($input->type->value) . ':' . $input->text,
        ]);

        // User message (question)
        $conversation->messages()->create([
            'role' => 'user',
            'content' => $question,
            'meta' => [
                'type' => $input->type->value,
                'input' => $input->text,
                'persona' => $persona->value,
            ],
        ]);

        // Assistant message (AI response)
        $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $aiResponse,
            'meta' => [
                'type' => $input->type->value,
                'input' => $input->text,
                'persona' => $persona->value,
                'question' => $question,
                'raw_data' => $raw,
                'force_refresh' => $forceRefresh,
            ],
        ]);

        return $conversation;
    }

    /**
     * Paginate conversations, newest first.
     */
    public function getPagination(): Paginator
    {
        return Conversation::latest()
            ->simplePaginate($this->perPage);
    }
}
