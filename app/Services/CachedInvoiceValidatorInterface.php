<?php

declare(strict_types=1);

namespace App\Services;

interface CachedInvoiceValidatorInterface
{
    public function isValidCachedInvoice(?array $cached): bool;
}
