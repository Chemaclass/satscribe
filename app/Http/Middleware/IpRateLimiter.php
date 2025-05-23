<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Data\InvoiceData;
use App\Services\Alby\AlbyClientInterface;
use Carbon\Carbon;
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
        $invoiceCacheKey = "ln_invoice:{$shortHash}";

        $this->cache->put(
            self::createCacheKey($shortHash),
            $trackingId,
            now()->addSeconds($this->lnInvoiceExpirySeconds)
        );

        if (RateLimiter::tooManyAttempts($key, $this->maxAttempts)) {
            $cached = $this->cache->get($invoiceCacheKey);

            if ($this->isValidCachedInvoice($cached)) {
                return response()->json([
                    'status' => 'rate_limited',
                    'key' => $key,
                    'retryAfter' => RateLimiter::availableIn($key),
                    'maxAttempts' => $this->maxAttempts,
                    'invoice' => $cached,
                ], Response::HTTP_TOO_MANY_REQUESTS);
            }

            $invoice = $this->albyClient->createInvoice(new InvoiceData(
                amount: $this->lnInvoiceAmountInSats,
                memo: sprintf('Zap to keep Satscribe alive ⚡️ #%s', $shortHash),
                expiry: $this->lnInvoiceExpirySeconds,
            ));

            // Cache the invoice until shortly before expiry
            $this->cache->put(
                $invoiceCacheKey,
                $invoice,
                now()->addSeconds($this->lnInvoiceExpirySeconds - 10)
            );

            return response()->json([
                'status' => 'rate_limited',
                'key' => $key,
                'retryAfter' => RateLimiter::availableIn($key),
                'maxAttempts' => $this->maxAttempts,
                'invoice' => $invoice,
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        RateLimiter::hit($key, 60 * 60); // Reset after 1 hour

        return $next($request);
    }

    private function isValidCachedInvoice(mixed $cached): bool
    {
        if (
            !is_array($cached) ||
            !isset($cached['payment_hash'], $cached['payment_request'], $cached['created_at'], $cached['expires_in'])
        ) {
            return false;
        }

        $expiresAt = Carbon::parse($cached['created_at'])->addSeconds($cached['expires_in']);

        return now()->lessThan($expiresAt);
    }
}
