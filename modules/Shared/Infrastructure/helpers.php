<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Request;

if (!\function_exists('client_ip')) {
    function client_ip(): string
    {
        /**
         * @see https://developers.cloudflare.com/fundamentals/reference/http-headers/#cf-connecting-ip
         */
        return Request::header('CF-Connecting-IP')
            ?? Request::ip();
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
        return session('nostr_pubkey');
    }
}
