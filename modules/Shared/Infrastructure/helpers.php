<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Request;

if (!\function_exists('client_ip')) {
    function client_ip(): string
    {
        // Anything read straight off a header is client-controlled: this used
        // to prefer CF-Connecting-IP, so rotating that header bought an
        // unlimited guest quota. Request::ip() is the trusted-proxy chain, so
        // putting Cloudflare back in front means adding its ranges to
        // trustProxies, not reading a header here.
        return Request::ip() ?? '';
    }
}

if (!\function_exists('tracking_id')) {
    function tracking_id(): string
    {
        return nostr_pubkey() ?: hash('sha256', client_ip() . Request::userAgent());
    }
}

if (!\function_exists('nostr_pubkey')) {
    function nostr_pubkey(): ?string
    {
        $pubkey = session('nostr_pubkey');

        // The session is untyped storage: anything a past request wrote under
        // this key comes back as-is, and a non-string would flow straight into
        // tracking_id() and the chat ownership checks.
        return \is_string($pubkey) ? $pubkey : null;
    }
}

if (!\function_exists('as_string')) {
    /**
     * Request and session fields are whatever the client sent. Casting an array
     * to string raises "Array to string conversion", which Laravel escalates to
     * an ErrorException — so `?q[]=x` was enough to 500 an endpoint. Anything
     * that is not stringable falls back to the default instead.
     */
    function as_string(mixed $value, string $default = ''): string
    {
        if (\is_string($value)) {
            return $value;
        }

        if (\is_int($value) || \is_float($value) || $value instanceof Stringable) {
            return (string) $value;
        }

        return $default;
    }
}

if (!\function_exists('config_int')) {
    /**
     * config() is typed as mixed, so every caller cast it and a missing or
     * malformed key silently became 0 — which for a rate-limit ceiling means
     * paywalling everyone. Fail loudly instead.
     */
    function config_int(string $key): int
    {
        $value = config($key);

        if (!is_numeric($value)) {
            throw new RuntimeException("Config [{$key}] must be numeric, got " . get_debug_type($value) . '.');
        }

        return (int) $value;
    }
}
