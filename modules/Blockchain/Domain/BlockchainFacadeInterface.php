<?php

namespace Modules\Blockchain\Domain;

use Modules\Blockchain\Domain\Data\BlockchainData;
use Modules\Chat\Domain\Data\PromptInput;

interface BlockchainFacadeInterface
{
    public function getMaxPossibleBlockHeight(): int;

    public function getCurrentBlockHeight(): int;

    public function getBlockchainData(PromptInput $input): BlockchainData;
}
