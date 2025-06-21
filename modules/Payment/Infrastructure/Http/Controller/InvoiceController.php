<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Http\Controller;

use Illuminate\Http\JsonResponse;
use Modules\Payment\Application\InvoiceService;

final readonly class InvoiceController
{
    public function __construct(private InvoiceService $service)
    {
    }

    public function status(string $identifier): JsonResponse
    {
        return response()->json(['paid' => $this->service->isPaid($identifier)]);
    }
}
