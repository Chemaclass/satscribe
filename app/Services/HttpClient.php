<?php
declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\Factory as IlluminateHttpClient;
use Illuminate\Http\Client\Response;

final readonly class HttpClient implements HttpClientInterface
{
    public function __construct(
        private IlluminateHttpClient $http,
    ) {
    }

    public function get(string $url): Response
    {
        return $this->http->get($url);
    }
}
