<?php

declare(strict_types=1);

namespace Modules\Payment\Domain;

use Modules\OpenAI\Domain\Data\ModelSelection;
use Modules\Payment\Domain\Exception\PremiumCreditRequired;

interface PremiumAccessInterface
{
    /**
     * Consumes one premium message when the selection needs one.
     *
     * A selection carrying the visitor's own key is theirs to pay for and is
     * left alone; only models this deployment funds are gated.
     *
     * @throws PremiumCreditRequired
     */
    public function authorise(?ModelSelection $selection): void;
}
