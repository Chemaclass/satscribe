<?php

declare(strict_types=1);

namespace Modules\Blockchain\Domain;

use Modules\Blockchain\Domain\Data\BlockchainData;
use Modules\Chat\Domain\Data\PromptInput;

interface BlockchainServiceInterface
{
    public function getBlockchainData(PromptInput $input): BlockchainData;
}
