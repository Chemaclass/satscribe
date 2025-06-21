<?php

declare(strict_types=1);

namespace Modules\Payment\Application;

use Modules\Payment\Domain\AlbyClientInterface;

final readonly class InvoiceService
{
    public function __construct(private AlbyClientInterface $albyClient)
    {
    }

    public function isPaid(string $identifier): bool
    {
        return $this->albyClient->isInvoicePaid($identifier);
    }
}
