<?php

declare(strict_types=1);

namespace Modules\Blockchain\Application;

use Modules\Blockchain\Application\Blockstream\BlockchainService;
use Modules\Blockchain\Application\Blockstream\BlockHeightProvider;
use Modules\Blockchain\Domain\BlockchainFacadeInterface;
use Modules\Shared\Domain\Data\Blockchain\BlockchainData;
use Modules\Shared\Domain\Data\Chat\PromptInput;

final readonly class BlockchainFacade implements BlockchainFacadeInterface
{
    public function __construct(
        private BlockHeightProvider $blockHeightProvider,
        private BlockchainService $blockchainService,
    ) {
    }

    public function getMaxPossibleBlockHeight(): int
    {
        return $this->blockHeightProvider->getMaxPossibleBlockHeight();
    }

    public function getCurrentBlockHeight(): int
    {
        return $this->blockHeightProvider->getCurrentBlockHeight();
    }

    public function getBlockchainData(PromptInput $input): BlockchainData
    {
        return $this->blockchainService->getBlockchainData($input);
    }
}
