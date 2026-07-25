<?php

declare(strict_types=1);

namespace Modules\UtxoTrace\Infrastructure\Http\Controller;

use Illuminate\Http\Request;
use Modules\UtxoTrace\Domain\UtxoTraceFacadeInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

final readonly class TraceUtxoController
{
    /**
     * Each level walks every input of every transaction found at the level
     * above, so the Blockstream requests one call makes grow exponentially with
     * depth. This route is unauthenticated and unthrottled and the handler runs
     * with a 300s limit, so an unbounded depth let a single request pin a worker
     * and hammer the upstream API. The page itself only ever asks for 2.
     */
    public const MAX_DEPTH = 5;

    public function __construct(
        private UtxoTraceFacadeInterface $utxoTrace,
    ) {
    }

    public function get(Request $request, string $txid): JsonResponse
    {
        if (!preg_match('/^[0-9a-fA-F]{64}$/', $txid)) {
            return response()->json(['error' => 'Invalid txid'], 400);
        }

        $depth = min(max((int) $request->query('depth', '2'), 1), self::MAX_DEPTH);

        ini_set('max_execution_time', '300');

        return response()->json($this->utxoTrace->getUtxoBacktrace($txid, $depth));
    }
}
