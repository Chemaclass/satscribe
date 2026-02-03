<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;
use Modules\Shared\Application\HttpClient;
use Tests\TestCase;

final class HttpClientTest extends TestCase
{
    public function test_get_returns_successful_response(): void
    {
        Http::fake([
            'example.com/*' => Http::response(['data' => 'test'], 200),
        ]);

        $client = new HttpClient($this->httpFactory());

        $response = $client->get('https://example.com/api');

        $this->assertTrue($response->successful());
        $this->assertSame('test', $response->json('data'));
    }

    public function test_get_handles_server_error_response(): void
    {
        Http::fake([
            'example.com/*' => Http::response('Server Error', 500),
        ]);

        $client = new HttpClient(
            http: $this->httpFactory(),
            timeoutSeconds: 5,
            retryTimes: 1,
            retryDelayMs: 10,
        );

        $response = $client->get('https://example.com/api');

        $this->assertTrue($response->serverError());
        $this->assertSame(500, $response->status());
    }

    public function test_get_handles_rate_limit_response(): void
    {
        Http::fake([
            'example.com/*' => Http::response('Rate limited', 429),
        ]);

        $client = new HttpClient(
            http: $this->httpFactory(),
            timeoutSeconds: 5,
            retryTimes: 1,
            retryDelayMs: 10,
        );

        $response = $client->get('https://example.com/api');

        $this->assertSame(429, $response->status());
    }

    public function test_get_handles_client_error(): void
    {
        Http::fake([
            'example.com/*' => Http::response('Not Found', 404),
        ]);

        $client = new HttpClient(
            http: $this->httpFactory(),
            timeoutSeconds: 5,
            retryTimes: 3,
            retryDelayMs: 10,
        );

        $response = $client->get('https://example.com/api');

        $this->assertTrue($response->clientError());
        $this->assertSame(404, $response->status());
    }

    public function test_with_token_returns_pending_request(): void
    {
        Http::fake([
            'example.com/*' => Http::response(['data' => 'token-test'], 200),
        ]);

        $client = new HttpClient(
            http: $this->httpFactory(),
            timeoutSeconds: 10,
            retryTimes: 2,
            retryDelayMs: 50,
        );

        $response = $client->withToken('test-token')->get('https://example.com/api');

        $this->assertTrue($response->successful());
        $this->assertSame('token-test', $response->json('data'));
    }

    public function test_default_configuration_values(): void
    {
        Http::fake([
            '*' => Http::response(['ok' => true], 200),
        ]);

        $client = new HttpClient($this->httpFactory());

        $response = $client->get('https://example.com/test');

        $this->assertTrue($response->successful());
    }

    public function test_custom_timeout_configuration(): void
    {
        Http::fake([
            '*' => Http::response(['ok' => true], 200),
        ]);

        $client = new HttpClient(
            http: $this->httpFactory(),
            timeoutSeconds: 30,
        );

        $response = $client->get('https://example.com/test');

        $this->assertTrue($response->successful());
    }
    private function httpFactory(): HttpFactory
    {
        return $this->app->make(HttpFactory::class);
    }
}
