<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Data\InvoiceData;
use App\Services\Alby\AlbyClientInterface;
use Closure;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

final readonly class IpRateLimiter
{
    public function __construct(
        private AlbyClientInterface $albyClient,
        private CacheRepository $cache,
        private int $maxAttempts,
        private int $lnInvoiceAmountInSats,
        private int $lnInvoiceExpirySeconds,
    ) {
    }

    public static function containsHash(string $memo): bool
    {
        return str_contains($memo, '#');
    }

    public static function createRateLimitKey(string $trackingId): string
    {
        return 'ip_rate_limit_'.$trackingId;
    }

    public static function createCacheKey(string $hash): string
    {
        return 'invoice_tracking_mapping_'.$hash;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $trackingId = tracking_id();

        $key = self::createRateLimitKey($trackingId);
        $shortHash = substr(md5($key), 0, 8);

        $this->cache->put(self::createCacheKey($shortHash), $trackingId, now()->addHour());

        if (RateLimiter::tooManyAttempts($key, $this->maxAttempts)) {
            return response()->json([
                'status' => 'rate_limited',
                'key' => $key,
                'retryAfter' => RateLimiter::availableIn($key),
                'maxAttempts' => $this->maxAttempts,
                'invoice' => $this->albyClient->createInvoice(
                    new InvoiceData(
                        amount: $this->lnInvoiceAmountInSats,
                        memo: sprintf('Zap to keep Satscribe alive ⚡️ #%s', $shortHash),
                        expiry: $this->lnInvoiceExpirySeconds,
                    )
                ),
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        RateLimiter::hit($key, 60 * 60); // Reset after 1 hour

        return $next($request);
    }
}
