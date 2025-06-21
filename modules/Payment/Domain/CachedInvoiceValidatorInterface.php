<?php

declare(strict_types=1);

namespace Modules\Payment\Domain;

interface CachedInvoiceValidatorInterface
{
    public function isValidCachedInvoice(?array $cached): bool;
}
