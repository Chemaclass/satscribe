<?php

declare(strict_types=1);

namespace Modules\Nostr\Infrastructure\Http\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Nostr\Domain\EventSignatureVerifierInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

use function is_array;
use function is_string;

final readonly class NostrAuthController
{
    public function __construct(
        private EventSignatureVerifierInterface $verifier,
        private LoggerInterface $logger,
    ) {
    }

    public function challenge(Request $request): JsonResponse
    {
        $challenge = bin2hex(random_bytes(32));
        $request->session()->put('nostr_challenge', $challenge);

        return response()->json(['challenge' => $challenge]);
    }

    public function login(Request $request): JsonResponse
    {
        $event = $request->input('event');

        if (is_string($event)) {
            $decoded = json_decode($event, true);
            if (is_array($decoded)) {
                $event = $decoded;
            }
        }

        if (!is_array($event)) {
            $this->logger->debug('NostrAuth login failed: event not array');
            return response()->json(['error' => 'Invalid event'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $pubkey = strtolower((string) ($event['pubkey'] ?? ''));

        if (!preg_match('/^[0-9a-f]{64}$/', $pubkey)) {
            $this->logger->debug('NostrAuth login failed: invalid pubkey', ['pubkey' => $pubkey]);
            return response()->json(['error' => 'Invalid pubkey'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $challenge = $request->session()->pull('nostr_challenge');
        if (!$challenge || ($event['content'] ?? '') !== $challenge) {
            $this->logger->debug('NostrAuth login failed: invalid challenge', [
                'expected' => $challenge,
                'received' => $event['content'] ?? null,
            ]);
            return response()->json(['error' => 'Invalid challenge'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!$this->verifier->verify($event)) {
            $this->logger->debug('NostrAuth login failed: invalid signature', [
                'id' => $event['id'] ?? null,
            ]);
            return response()->json(['error' => 'Invalid signature'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $request->session()->put('nostr_pubkey', $pubkey);

        return response()->json(['pubkey' => $pubkey]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->session()->forget('nostr_pubkey');

        return response()->json(['ok' => true]);
    }
}
