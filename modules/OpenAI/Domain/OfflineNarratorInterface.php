<?php

declare(strict_types=1);

namespace Modules\OpenAI\Domain;

use Modules\Shared\Domain\Data\Blockchain\BlockchainData;
use Modules\Shared\Domain\Enum\Chat\PromptPersona;

interface OfflineNarratorInterface
{
    /**
     * A readable answer assembled from the blockchain data alone, for when no
     * model can be reached.
     *
     * Every figure it states was fetched from Blockstream: the phrasing varies
     * by persona, the facts never do. It invents nothing, because an invented
     * blockchain fact presented as an explanation is worse than no answer.
     */
    public function narrate(BlockchainData $data, PromptPersona $persona): string;
}
