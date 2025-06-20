<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class TraceUtxoTest extends TestCase
{
    public function test_invalid_txid_returns_400(): void
    {
        $response = $this->get('/api/trace-utxo/invalid');

        $response->assertStatus(400);
    }

    public function test_invalid_txid_page_returns_400(): void
    {
        $response = $this->get('/trace-utxo/invalid');

        $response->assertStatus(400);
    }
}
