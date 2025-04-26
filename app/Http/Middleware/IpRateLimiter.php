<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Data\InvoiceData;
use App\Services\Alby\AlbyClientInterface;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

final readonly class IpRateLimiter
{
    public function __construct(
        private AlbyClientInterface $albyClient,
        private int $maxAttempts,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        $key = 'ip_rate_limit_'.$ip;

        if (RateLimiter::tooManyAttempts($key, $this->maxAttempts)) {
            RateLimiter::clear($key);

            return response()->json([
                'status' => 'rate_limited',
                'key' => $key,
                'retryAfter' => RateLimiter::availableIn($key),
                'lnInvoice' => $this->albyClient->addInvoice(
                    new InvoiceData(
                        amount: 1000,
                        memo: 'Tip to unlock more Satscribe requests',
                        expiry: 60 * 5,
                    )
                ),
            ], 429);
        }

        RateLimiter::hit($key, 60 * 60); // Reset after 1 hour

        return $next($request);
    }
}
