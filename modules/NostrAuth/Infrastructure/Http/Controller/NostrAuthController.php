<?php

declare(strict_types=1);

namespace Modules\NostrAuth\Infrastructure\Http\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use Modules\NostrAuth\Application\EventSignatureVerifier;

use Symfony\Component\HttpFoundation\Response;

use function is_array;

final readonly class NostrAuthController
{
    public function __construct(private EventSignatureVerifier $verifier)
    {
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
        if (!is_array($event)) {
            return response()->json(['error' => 'Invalid event'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $pubkey = strtolower((string) ($event['pubkey'] ?? ''));

        if (!preg_match('/^[0-9a-f]{64}$/', $pubkey)) {
            return response()->json(['error' => 'Invalid pubkey'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $challenge = $request->session()->pull('nostr_challenge');
        if (!$challenge || ($event['content'] ?? '') !== $challenge) {
            return response()->json(['error' => 'Invalid challenge'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!$this->verifier->verify($event)) {
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
