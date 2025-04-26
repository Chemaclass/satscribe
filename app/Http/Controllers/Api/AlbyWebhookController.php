<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Svix\Webhook;
use Symfony\Component\HttpFoundation\JsonResponse;

final class AlbyWebhookController
{
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $this->verifySignature($request);

        if ($payload === []) {
            Log::warning('Invalid Alby webhook signature');
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        Log::info('Webhook payload', ['payload' => $payload]);

        if (($payload['type'] ?? '') === 'incoming' && ($payload['state'] ?? '') === 'SETTLED') {
            $this->handleInvoiceSettled($payload);
        } else {
            Log::info('Unhandled webhook payload', ['payload' => $payload]);
        }

        return response()->json(['success' => true]);
    }

    private function verifySignature(Request $request): array
    {
        // todo: inject config later
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
        $invoiceHash = $payload['payment_hash'] ?? null;
        $memo = $payload['memo'] ?? '';
        Log::info('Invoice settled', ['$memo' => $memo]);

        if (str_contains($memo, '#')) {
            $shortHash = $this->extractShortHash($memo);
            if ($shortHash) {
                $ip = cache()->pull('invoice_ip_mapping_' . $shortHash); // pull = get + delete

                if ($ip) {
                    $key = 'ip_rate_limit_' . $ip;
                    RateLimiter::clear($key);
                    Log::info('Rate limit cleared for IP', ['ip' => $ip]);
                } else {
                    Log::warning('No IP found for hash', ['shortHash' => $shortHash]);
                }
            }
        }

        Log::info('Invoice settled', [
            'payment_hash' => $invoiceHash,
            'amount' => $payload['amount'] ?? 0,
        ]);
    }

    private function extractShortHash(string $memo): ?string
    {
        if (preg_match('/#([a-f0-9]{8})/', $memo, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
