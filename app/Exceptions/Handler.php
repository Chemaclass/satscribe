<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;

final class Handler extends ExceptionHandler
{
    public function render($request, Throwable $e): Response
    {
        if ($e instanceof MethodNotAllowedHttpException && app()->environment('production')) {
            Log::warning('Invalid HTTP method used', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
            ]);

            return response()->json([
                'message' => 'Method Not Allowed.',
            ], Response::HTTP_METHOD_NOT_ALLOWED);
        }

        if ($e instanceof ThrottleRequestsException) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        return parent::render($request, $e);
    }
}
