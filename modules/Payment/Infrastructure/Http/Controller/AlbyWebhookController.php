<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Http\Controller;

use Illuminate\Http\Request;
use Modules\Payment\Application\AlbySettleWebhookAction;
use Modules\Payment\Domain\Exception\InvalidAlbyWebhookSignatureException;
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
