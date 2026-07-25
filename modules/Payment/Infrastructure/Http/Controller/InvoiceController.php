<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Http\Controller;

use Illuminate\Http\JsonResponse;
use Modules\Payment\Domain\ConfirmInvoicePaymentActionInterface;

final readonly class InvoiceController
{
    public function __construct(
        private ConfirmInvoicePaymentActionInterface $confirmPayment,
    ) {
    }

    public function status(string $identifier): JsonResponse
    {
        return response()->json([
            'paid' => $this->confirmPayment->execute($identifier, tracking_id()),
        ]);
    }
}
