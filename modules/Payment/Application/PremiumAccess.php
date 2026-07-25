<?php

declare(strict_types=1);

namespace Modules\Payment\Application;

use Modules\OpenAI\Domain\Data\ModelSelection;
use Modules\Payment\Domain\Exception\PremiumCreditRequired;
use Modules\Payment\Domain\PremiumAccessInterface;
use Modules\Payment\Domain\PremiumCreditsInterface;

final readonly class PremiumAccess implements PremiumAccessInterface
{
    public function __construct(
        private PremiumCreditsInterface $credits,
        private string $npub,
        private int $packSats,
        private int $packMessages,
    ) {
    }

    public function authorise(?ModelSelection $selection): void
    {
        // No selection means the automatic default, which is always a model
        // this deployment already funds for free.
        if (!$selection instanceof ModelSelection) {
            return;
        }

        // The visitor is paying their own provider, so none of this applies.
        if ($selection->usesUserKey) {
            return;
        }

        if (!$selection->provider->isPremium($selection->model)) {
            return;
        }

        // Credit follows the Nostr identity, so there is nothing to charge
        // against without one.
        if ($this->npub === '') {
            throw PremiumCreditRequired::notLoggedIn();
        }

        if (!$this->credits->spendOne($this->npub)) {
            throw PremiumCreditRequired::noBalance($this->packSats, $this->packMessages);
        }
    }
}
