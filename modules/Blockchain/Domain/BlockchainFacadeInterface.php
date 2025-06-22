<?php

namespace Modules\Blockchain\Domain;

use Modules\Shared\Domain\Data\Blockchain\BlockchainData;
use Modules\Shared\Domain\Data\Chat\PromptInput;

interface BlockchainFacadeInterface
{
    public function getMaxPossibleBlockHeight(): int;

    public function getCurrentBlockHeight(): int;

    public function getBlockchainData(PromptInput $input): BlockchainData;
}
