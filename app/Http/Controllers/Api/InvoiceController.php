<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;

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
