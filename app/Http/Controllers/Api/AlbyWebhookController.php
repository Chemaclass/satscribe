<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Svix\Webhook;
use Symfony\Component\HttpFoundation\JsonResponse;

final class AlbyWebhookController
{
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $this->verifySignature($request);

        if ($payload === []) {
            // todo: inject logger
            Log::warning('Invalid Alby webhook signature');
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        if (($payload['type'] ?? '') === 'incoming' && ($payload['state'] ?? '') === 'SETTLED') {
            $this->handleInvoiceSettled($payload);
        } else {
            Log::info('Unhandled webhook payload', ['payload' => $payload]);
        }

        return response()->json(['success' => true]);
    }

    private function verifySignature(Request $request): array
    {
        // todo: inject config
        $webhookSecret = config('services.alby.webhook_secret');

        if (empty($webhookSecret)) {
            logger()->warning('Webhook secret not configured');
            return [];
        }

        try {
            $payload = $request->getContent(); // full raw body

            $wh = new Webhook($webhookSecret);
            $wh->verify($payload, [
                'svix-id' => $request->header('svix-id'),
                'svix-timestamp' => $request->header('svix-timestamp'),
                'svix-signature' => $request->header('svix-signature'),
            ]);

            Log::info('Webhook successfully verified');
            return json_decode($payload, true);
        } catch (\Throwable $e) {
            logger()->warning('Webhook verification failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function handleInvoiceSettled(array $payload): void
    {
       // todo: not implemented yet...
    }
}
