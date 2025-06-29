<?php

declare(strict_types=1);

namespace Modules\Chat\Domain\Repository;

use App\Models\Chat;
use Illuminate\Contracts\Pagination\Paginator;
use Modules\Shared\Domain\Data\Blockchain\BlockchainDataInterface;
use Modules\Shared\Domain\Data\Chat\PromptInput;
use Modules\Shared\Domain\Enum\Chat\PromptPersona;

interface ChatRepositoryInterface
{
    public function findByCriteria(PromptInput $input, PromptPersona $persona, string $question = ''): ?Chat;

    public function createChat(
        PromptInput $input,
        string $aiResponse,
        BlockchainDataInterface $blockchainData,
        PromptPersona $persona,
        string $question,
        bool $isPublic,
    ): Chat;

    public function addMessageToChat(Chat $chat, string $userMessage, string $assistantResponse): void;

    public function getPagination(bool $showAll): Paginator;

    public function getTotalChats(): int;
}
