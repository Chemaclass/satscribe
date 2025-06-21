<?php

declare(strict_types=1);

namespace Modules\Chat\Domain\Repository;

use App\Models\Chat;
use Illuminate\Contracts\Pagination\Paginator;
use Modules\Blockchain\Domain\Data\BlockchainDataInterface;
use Modules\Chat\Domain\Data\PromptInput;
use Modules\Chat\Domain\Enum\PromptPersona;

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
