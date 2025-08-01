<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Modules\Shared\Infrastructure\Http\Middleware\SetLocale;

return Application::configure(basePath: \dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(static function (Middleware $middleware): void {
        $middleware->appendToGroup('web', SetLocale::class);
    })
    ->withExceptions(static function (Exceptions $exceptions): void {

    })->create();
