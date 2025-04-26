<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\AlbySettleWebhookAction;
use App\Exceptions\InvalidAlbyWebhookSignatureException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

final class AlbyWebhookController
{
    public function __invoke(Request $request, AlbySettleWebhookAction $action): JsonResponse
    {
        try {
            $action->execute(
                payload: $request->getContent(),
                svixId: $request->header('svix-id'),
                svixTimestamp: $request->header('svix-timestamp'),
                svixSignature: $request->header('svix-signature'),
            );

            return response()->json(['success' => true]);
        } catch (InvalidAlbyWebhookSignatureException) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }
    }
}
