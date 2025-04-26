<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Services\Alby\AlbyClientInterface;
use Illuminate\Http\JsonResponse;

final readonly class InvoiceController
{
    public function __construct(
        private AlbyClientInterface $albyClient
    ) {
    }

    public function status(string $identifier): JsonResponse
    {
        $isPaid = $this->albyClient->isInvoicePaid($identifier);

        return response()->json(['paid' => $isPaid]);
    }
}
