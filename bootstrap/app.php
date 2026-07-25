<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Modules\Shared\Infrastructure\Http\Middleware\SetLocale;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: \dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(static function (Middleware $middleware): void {
        $middleware->appendToGroup('web', SetLocale::class);

        // Laravel 11 dropped the default API throttle, so /api/* was entirely
        // unmetered — including the UTXO trace, which fans out to Blockstream.
        // The busiest honest caller is the paywall modal polling invoice status
        // once every 1.5s (~40/min), so this leaves roughly threefold headroom.
        $middleware->appendToGroup('api', 'throttle:120,1');
    })
    ->withExceptions(static function (Exceptions $exceptions): void {
        // The OpenAI daily cap is enforced by throwing ThrottleRequestsException
        // from the chat actions. Without this the browser receives Laravel's HTML
        // error page, which the fetch()-based chat UI cannot present.
        $exceptions->render(static fn (ThrottleRequestsException $e, Request $request) => $request->expectsJson()
            ? response()->json(['message' => $e->getMessage()], Response::HTTP_TOO_MANY_REQUESTS)
            : null);
    })->create();
