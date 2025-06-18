<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Data\InvoiceData;
use App\Services\Alby\AlbyClientInterface;
use App\Services\CachedInvoiceValidatorInterface;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

final readonly class IpRateLimiter
{
    public function __construct(
        private CachedInvoiceValidatorInterface $invoiceValidator,
        private AlbyClientInterface $albyClient,
        private CacheRepository $cache,
        private LoggerInterface $logger,
        private CarbonInterface $now,
        private int $maxAttempts,
        private int $lnInvoiceAmountInSats,
        private int $lnInvoiceExpirySeconds,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $trackingId = tracking_id();
        $rateLimitKey = self::createRateLimitKey($trackingId);
        $shortHash = substr(md5($rateLimitKey), 0, 8);
        $invoiceCacheKey = "ln_invoice:{$shortHash}";

        $this->logTracking($trackingId, $invoiceCacheKey);
        $this->cacheTrackingMapping($shortHash, $trackingId);

        if (RateLimiter::tooManyAttempts($rateLimitKey, $this->maxAttempts)) {
            return $this->handleRateLimited($rateLimitKey, $invoiceCacheKey, $shortHash);
        }

        $this->logRateLimitHit($rateLimitKey);
        RateLimiter::hit($rateLimitKey, 3600); // 1-hour window

        return $next($request);
    }

    private function handleRateLimited(string $rateLimitKey, string $invoiceCacheKey, string $shortHash): Response
    {
        $this->logger->info('Too many attempts, preparing invoice', ['key' => $rateLimitKey]);

        $cachedInvoice = $this->cache->get($invoiceCacheKey);

        if ($this->invoiceValidator->isValidCachedInvoice($cachedInvoice)) {
            $this->logger->info('Using valid cached invoice', ['invoice' => $cachedInvoice]);
            return $this->buildRateLimitedResponse($rateLimitKey, $cachedInvoice);
        }

        $invoice = $this->buildInvoice($shortHash);
        $this->cacheInvoice($invoiceCacheKey, $invoice);

        return $this->buildRateLimitedResponse($rateLimitKey, $invoice);
    }

    private function buildInvoice(string $shortHash): array
    {
        return $this->albyClient->createInvoice(new InvoiceData(
            amount: $this->lnInvoiceAmountInSats,
            memo: sprintf('Zap to keep Satscribe alive ⚡️ #%s', $shortHash),
            expiry: $this->lnInvoiceExpirySeconds,
        ));
    }

    private function cacheInvoice(string $key, array $invoice): void
    {
        $this->logger->info('Caching new invoice', ['invoiceCacheKey' => $key]);

        $this->cache->put(
            $key,
            $invoice,
            $this->now->copy()->addSeconds($this->lnInvoiceExpirySeconds - 10)
        );
    }

    private function cacheTrackingMapping(string $hash, string $trackingId): void
    {
        $this->cache->put(
            self::createCacheKey($hash),
            ['tracking_id' => $trackingId],
            $this->now->copy()->addSeconds($this->lnInvoiceExpirySeconds)
        );
    }

    private function logTracking(string $trackingId, string $cacheKey): void
    {
        $this->logger->info('Tracking request', [
            'tracking_id' => $trackingId,
            'invoiceCacheKey' => $cacheKey,
        ]);
    }

    private function logRateLimitHit(string $key): void
    {
        $this->logger->info('Rate limiter hit', [
            'key' => $key,
            'attempts' => RateLimiter::attempts($key),
        ]);
    }

    private function buildRateLimitedResponse(string $key, array $invoice): Response
    {
        return response()->json([
            'status' => 'rate_limited',
            'key' => $key,
            'retryAfter' => RateLimiter::availableIn($key),
            'maxAttempts' => $this->maxAttempts,
            'invoice' => $invoice,
        ], Response::HTTP_TOO_MANY_REQUESTS);
    }


    public static function createRateLimitKey(string $trackingId): string
    {
        return 'ip_rate_limit_'.$trackingId;
    }

    public static function createCacheKey(string $hash): string
    {
        return 'invoice_tracking_mapping_'.$hash;
    }
}
