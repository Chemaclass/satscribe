<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Services\UtxoTraceService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

final readonly class TraceUtxoController
{
    public function __construct(
        private UtxoTraceService $utxoTraceService,
    ) {
    }

    public function get(Request $request, string $txid): JsonResponse
    {
        if (!preg_match('/^[0-9a-fA-F]{64}$/', $txid)) {
            return response()->json(['error' => 'Invalid txid'], 400);
        }

        $depth = max((int) $request->query('depth', 2), 1);

        return response()->json($this->utxoTraceService->trace($txid, $depth));
    }
}
