<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Http\Controller;

use Illuminate\Http\JsonResponse;
use Modules\Payment\Application\AlbyClient;

final readonly class InvoiceController
{
    public function __construct(
        private AlbyClient $albyClient,
    ) {
    }

    public function status(string $identifier): JsonResponse
    {
        return response()->json([
            'paid' => $this->albyClient->isInvoicePaid($identifier),
        ]);
    }
}
