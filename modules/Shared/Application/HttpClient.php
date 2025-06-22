<?php

declare(strict_types=1);

namespace Modules\Shared\Application;

use Illuminate\Http\Client\Factory as IlluminateHttpClient;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Modules\Shared\Domain\HttpClientInterface;

use function assert;

final readonly class HttpClient implements HttpClientInterface
{
    public function __construct(
        private IlluminateHttpClient $http,
    ) {
    }

    public function get(string $url, array $query = []): Response
    {
        return $this->http->get($url, $query);
    }

    public function withToken(string $token, string $type = 'Bearer'): PendingRequest
    {
        $request = $this->http->withToken($token, $type);
        assert($request instanceof PendingRequest);

        return $request;
    }
}
