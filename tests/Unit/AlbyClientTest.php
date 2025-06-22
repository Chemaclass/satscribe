<?php

declare(strict_types=1);

namespace Tests\Unit;

use Modules\Payment\Application\AlbyClient;
use PHPUnit\Framework\TestCase;

final class AlbyClientTest extends TestCase
{
    public function test_connection_valid_when_token_provided(): void
    {
        $client = new AlbyClient('abc');

        $this->assertTrue($client->isConnectionValid());
    }

    public function test_connection_invalid_when_token_empty(): void
    {
        $client = new AlbyClient('');

        $this->assertFalse($client->isConnectionValid());
    }

    public function test_connection_invalid_when_token_zero_string(): void
    {
        $client = new AlbyClient('0');

        $this->assertFalse($client->isConnectionValid());
    }
}
