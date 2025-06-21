<?php
declare(strict_types=1);

namespace Modules\Blockchain\Application;

use Modules\Blockchain\Domain\BlockchainFacadeInterface;
use Modules\Blockchain\Domain\BlockchainServiceInterface;
use Modules\Blockchain\Domain\Data\BlockchainData;
use Modules\Chat\Domain\Data\PromptInput;

final readonly class BlockchainFacade implements BlockchainFacadeInterface
{
    public function __construct(
        private BlockHeightProvider $blockHeightProvider,
        private BlockchainServiceInterface $blockchainService,
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
