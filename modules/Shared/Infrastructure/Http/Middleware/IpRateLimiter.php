<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\Middleware;

use Carbon\CarbonInterface;
use Closure;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Payment\Domain\AlbyClientInterface;
use Modules\Payment\Domain\CachedInvoiceValidatorInterface;
use Modules\Shared\Domain\Data\Payment\InvoiceData;
use Modules\Shared\Domain\RateLimit\RateLimitKeys;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

use function sprintf;

final readonly class IpRateLimiter
{
    public function __construct(
        private CachedInvoiceValidatorInterface $invoiceValidator,
        private AlbyClientInterface $albyClient,
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
        $shortHash = substr(md5($rateLimitKey), 0, 8);
        $invoiceCacheKey = "ln_invoice:{$shortHash}";

        $this->logTracking($trackingId, $invoiceCacheKey);
        $this->cacheTrackingMapping($shortHash, $trackingId);

        $config = nostr_pubkey()
            ? config('services.rate_limit.nostr')
            : config('services.rate_limit.guest');

        $maxAttempts = (int) ($config['max_attempts'] ?? 0);
        $invoiceAmount = (int) ($config['invoice_amount'] ?? 0);

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
            $this->now->copy()->addSeconds($this->lnInvoiceExpirySeconds),
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

        $cachedInvoice = $this->cache->get($invoiceCacheKey);

        if ($this->invoiceValidator->isValidCachedInvoice($cachedInvoice)) {
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
        return $this->albyClient->createInvoice(new InvoiceData(
            amount: $invoiceAmount,
            memo: sprintf('Zap to keep Satscribe alive ⚡️ #%s', $shortHash),
            expiry: $this->lnInvoiceExpirySeconds,
        ));
    }

    /**
     * @param  array<string, mixed>  $invoice
     */
    private function cacheInvoice(string $key, array $invoice): void
    {
        $this->logger->debug('Caching new invoice', ['invoiceCacheKey' => $key]);

        $this->cache->put(
            $key,
            $invoice,
            $this->now->copy()->addSeconds($this->lnInvoiceExpirySeconds - 10),
        );
    }

    private function logRateLimitHit(string $key): void
    {
        $this->logger->debug('Rate limiter hit', [
            'key' => $key,
            'attempts' => RateLimiter::attempts($key),
        ]);
    }
}
