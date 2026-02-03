<?php

declare(strict_types=1);

namespace Modules\Shared\Application;

use Illuminate\Http\Client\Factory as IlluminateHttpClient;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Modules\Shared\Domain\HttpClientInterface;
use Throwable;

use function assert;

final readonly class HttpClient implements HttpClientInterface
{
    private const DEFAULT_TIMEOUT_SECONDS = 15;
    private const DEFAULT_RETRY_TIMES = 3;
    private const DEFAULT_RETRY_DELAY_MS = 100;

    public function __construct(
        private IlluminateHttpClient $http,
        private int $timeoutSeconds = self::DEFAULT_TIMEOUT_SECONDS,
        private int $retryTimes = self::DEFAULT_RETRY_TIMES,
        private int $retryDelayMs = self::DEFAULT_RETRY_DELAY_MS,
    ) {
    }

    public function get(string $url, array $query = []): Response
    {
        return $this->buildRequest()->get($url, $query);
    }

    public function withToken(string $token, string $type = 'Bearer'): PendingRequest
    {
        $request = $this->buildRequest()->withToken($token, $type);
        assert($request instanceof PendingRequest);

        return $request;
    }

    private function buildRequest(): PendingRequest
    {
        return $this->http
            ->timeout($this->timeoutSeconds)
            ->retry(
                times: $this->retryTimes,
                sleepMilliseconds: $this->retryDelayMs,
                when: static function (Throwable $e, PendingRequest $request): bool {
                    if ($e instanceof RequestException) {
                        $status = $e->response->status();

                        return $status >= 500 || $status === 429;
                    }

                    return false;
                },
                throw: false,
            );
    }
}
