<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Request;

if (!function_exists('client_ip')) {
    function client_ip(): string
    {
        /**
         * @see https://developers.cloudflare.com/fundamentals/reference/http-headers/#cf-connecting-ip
         */
        return Request::header('CF-Connecting-IP')
            ?? Request::ip();
    }
}
