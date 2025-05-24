<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\BlockchainDataInterface;
use App\Data\PromptInput;
use App\Enums\PromptPersona;
use App\Models\Chat;
use Illuminate\Contracts\Pagination\Paginator;

interface ChatRepositoryInterface
{
    public function findByCriteria(PromptInput $input, PromptPersona $persona, string $question = ''): ?Chat;

    public function createChat(
        PromptInput $input,
        string $aiResponse,
        BlockchainDataInterface $blockchainData,
        PromptPersona $persona,
        string $question,
        bool $isPrivate
    ): Chat;

    public function addMessageToChat(Chat $chat, string $userMessage, string $assistantResponse): void;

    public function getPagination(bool $showAll): Paginator;
}
