<?php

declare(strict_types=1);

namespace Modules\Shared\Domain;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

interface HttpClientInterface
{
    public function get(string $url, array $query = []): Response;

    public function withToken(string $token, string $type = 'Bearer'): PendingRequest;
}
