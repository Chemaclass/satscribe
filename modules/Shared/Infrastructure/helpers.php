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

if (!\function_exists('client_uuid')) {
    /** @todo not implemented yet */
    function client_uuid(): string
    {
        return (string) Request::header('X-Client-UUID', '');
    }
}

if (!\function_exists('tracking_id')) {
    function tracking_id(): string
    {
        return hash('sha256', client_ip() . client_uuid() . Request::userAgent());
    }
}
