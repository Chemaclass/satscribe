<?php

declare(strict_types=1);

namespace Modules\UtxoTrace\Infrastructure\Http\Controller;

use Illuminate\Http\Request;
use Modules\UtxoTrace\Application\UtxoTraceService;
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

        ini_set('max_execution_time', '300');

        return response()->json($this->utxoTraceService->traceWithReferences($txid, $depth));
    }
}
