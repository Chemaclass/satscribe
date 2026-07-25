<?php

declare(strict_types=1);

namespace Modules\Payment\Domain;

interface ConfirmPremiumPackActionInterface
{
    /**
     * Reports whether a pack invoice is settled and, when it is the caller's
     * own, grants the pack.
     *
     * The Alby webhook grants too, but delivery is not instant and can fail
     * outright. The buyer is sitting in front of the modal, so their own poll
     * is the faster and more reliable path; both are idempotent on the payment
     * hash, so whichever arrives second changes nothing.
     */
    public function execute(string $paymentHash, string $npub): bool;
}
