<?php

declare(strict_types=1);

namespace Modules\Payment\Domain;

interface CachedInvoiceValidatorInterface
{
    /**
     * @param  array<string, mixed>|null  $cached  a previously cached Alby invoice payload
     */
    public function isValidCachedInvoice(?array $cached): bool;
}
