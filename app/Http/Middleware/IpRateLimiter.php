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
        private int $lnInvoiceAmountInSats,
        private int $lnInvoiceExpirySeconds,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        $key = 'ip_rate_limit_' . $ip;
        $shortHash = substr(md5($key), 0, 8);

        $memo = sprintf('Zap to keep Satscribe flowing ⚡️ #%s', $shortHash);
        cache()->put('invoice_ip_mapping_' . $shortHash, $ip, now()->addHours(1));

        if (RateLimiter::tooManyAttempts($key, $this->maxAttempts)) {
            return response()->json([
                'status' => 'rate_limited',
                'key' => $key,
                'retryAfter' => RateLimiter::availableIn($key),
                'maxAttempts' => $this->maxAttempts,
                'lnInvoice' => $this->albyClient->addInvoice(
                    new InvoiceData(
                        amount: $this->lnInvoiceAmountInSats,
                        memo: $memo,
                        expiry: $this->lnInvoiceExpirySeconds,
                    )
                ),
            ], 429);
        }

        RateLimiter::hit($key, 60 * 60); // Reset after 1 hour

        return $next($request);
    }

    private function generateRateLimitMemo(string $ip): string
    {
        $hash = substr(md5('ip_rate_limit_'.$ip), 0, 8);

        return sprintf('Zap to keep Satscribe flowing ⚡️ #%s', $hash);
    }
}
