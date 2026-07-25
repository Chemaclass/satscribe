<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\Middleware;

use Carbon\CarbonInterface;
use Closure;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Shared\Domain\RateLimit\PaywallInvoiceIssuerInterface;
use Modules\Shared\Domain\RateLimit\RateLimitKeys;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

use function is_array;

final readonly class IpRateLimiter
{
    private const INVOICE_CACHE_MARGIN_SECONDS = 10;

    /**
     * How long a short-hash -> tracking-id mapping survives. It used to expire
     * with the invoice, so a visitor who paid near the deadline settled after
     * the mapping was gone: the webhook could no longer tell whose paywall to
     * lift, and the payment bought nothing. It outlives the invoice by a wide
     * margin now — it is two small strings.
     */
    private const TRACKING_MAPPING_TTL_SECONDS = 86400;

    public function __construct(
        private PaywallInvoiceIssuerInterface $invoiceIssuer,
        private CacheRepository $cache,
        private LoggerInterface $logger,
        private CarbonInterface $now,
        private int $lnInvoiceExpirySeconds,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $trackingId = tracking_id();
        $rateLimitKey = RateLimitKeys::forTrackingId($trackingId);
        $shortHash = RateLimitKeys::shortHashFor($trackingId);
        $invoiceCacheKey = RateLimitKeys::forInvoice($shortHash);

        $this->logTracking($trackingId, $invoiceCacheKey);
        $this->cacheTrackingMapping($shortHash, $trackingId);

        $tier = nostr_pubkey() ? 'nostr' : 'guest';

        $maxAttempts = config_int("services.rate_limit.{$tier}.max_attempts");
        $invoiceAmount = config_int("services.rate_limit.{$tier}.invoice_amount");

        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            return $this->handleRateLimited(
                $rateLimitKey,
                $invoiceCacheKey,
                $shortHash,
                $invoiceAmount,
                $maxAttempts,
            );
        }

        $this->logRateLimitHit($rateLimitKey);
        RateLimiter::hit($rateLimitKey, 3600); // 1-hour window

        return $next($request);
    }

    private function logTracking(string $trackingId, string $cacheKey): void
    {
        $this->logger->debug('Tracking request', [
            'tracking_id' => $trackingId,
            'invoiceCacheKey' => $cacheKey,
        ]);
    }

    private function cacheTrackingMapping(string $hash, string $trackingId): void
    {
        $this->cache->put(
            RateLimitKeys::forInvoiceTrackingMapping($hash),
            ['tracking_id' => $trackingId],
            $this->now->copy()->addSeconds(self::TRACKING_MAPPING_TTL_SECONDS),
        );
    }

    private function handleRateLimited(
        string $rateLimitKey,
        string $invoiceCacheKey,
        string $shortHash,
        int $invoiceAmount,
        int $maxAttempts,
    ): Response {
        $this->logger->info('Too many attempts, preparing invoice', ['key' => $rateLimitKey]);

        // Cache contents are untyped, so an entry written by an older release
        // need not still be an invoice payload.
        $cached = $this->cache->get($invoiceCacheKey);

        /** @var array<string, mixed>|null $cachedInvoice */
        $cachedInvoice = is_array($cached) ? $cached : null;

        if ($cachedInvoice !== null && $this->invoiceIssuer->isReusable($cachedInvoice)) {
            $this->logger->debug('Using valid cached invoice', ['invoice' => $cachedInvoice]);
            return $this->buildRateLimitedResponse($rateLimitKey, $cachedInvoice, $maxAttempts);
        }

        $invoice = $this->buildInvoice($shortHash, $invoiceAmount);
        $this->cacheInvoice($invoiceCacheKey, $invoice);

        return $this->buildRateLimitedResponse($rateLimitKey, $invoice, $maxAttempts);
    }

    /**
     * @param  array<string, mixed>  $invoice
     */
    private function buildRateLimitedResponse(string $key, array $invoice, int $maxAttempts): Response
    {
        return response()->json([
            'status' => 'rate_limited',
            'key' => $key,
            'retryAfter' => RateLimiter::availableIn($key),
            'maxAttempts' => $maxAttempts,
            'invoice' => $invoice,
        ], Response::HTTP_TOO_MANY_REQUESTS);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildInvoice(string $shortHash, int $invoiceAmount): array
    {
        return $this->invoiceIssuer->issue(
            $shortHash,
            $invoiceAmount,
            $this->lnInvoiceExpirySeconds,
        );
    }

    /**
     * Cached for slightly less than the invoice's own lifetime so a reused
     * invoice always has some life left when the client renders its QR.
     *
     * An expiry at or below the margin leaves no safe window, so nothing is
     * cached and every paywalled request mints a fresh invoice. That used to
     * happen silently: the subtraction went negative and the cache write was
     * discarded as already-expired.
     *
     * @param  array<string, mixed>  $invoice
     */
    private function cacheInvoice(string $key, array $invoice): void
    {
        $ttl = $this->lnInvoiceExpirySeconds - self::INVOICE_CACHE_MARGIN_SECONDS;

        if ($ttl <= 0) {
            $this->logger->debug('Invoice expiry too short to cache', [
                'invoiceCacheKey' => $key,
                'expiry' => $this->lnInvoiceExpirySeconds,
            ]);

            return;
        }

        $this->logger->debug('Caching new invoice', ['invoiceCacheKey' => $key]);

        $this->cache->put($key, $invoice, $this->now->copy()->addSeconds($ttl));
    }

    private function logRateLimitHit(string $key): void
    {
        $this->logger->debug('Rate limiter hit', [
            'key' => $key,
            'attempts' => RateLimiter::attempts($key),
        ]);
    }
}
