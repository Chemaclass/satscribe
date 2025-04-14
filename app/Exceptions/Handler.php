<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Throwable;

final class Handler extends ExceptionHandler
{
    public function render($request, Throwable $exception)
    {
        if ($exception instanceof ThrottleRequestsException) {
            if ($request->expectsHtml()) {
                return response()->view('errors.too-many-requests', [], 429);
            }

            return response()->json([
                'message' => 'Too many requests. Please try again later.',
            ], 429);
        }

        return parent::render($request, $exception);
    }
}
