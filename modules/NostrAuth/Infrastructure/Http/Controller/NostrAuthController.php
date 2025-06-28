<?php

declare(strict_types=1);

namespace Modules\NostrAuth\Infrastructure\Http\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class NostrAuthController
{
    public function login(Request $request): JsonResponse
    {
        $pubkey = strtolower((string) $request->input('pubkey'));

        if (!preg_match('/^[0-9a-f]{64}$/', $pubkey)) {
            return response()->json(['error' => 'Invalid pubkey'], 422);
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
