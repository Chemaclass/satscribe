<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\Blockchain\BlockchainData;
use App\Data\PromptInput;

interface BlockchainServiceInterface
{
    public function getBlockchainData(PromptInput $input): BlockchainData;
}
