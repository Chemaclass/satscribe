<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class TraceUtxoTest extends TestCase
{
    public function test_invalid_txid_returns_400(): void
    {
        config(['features.btc_price' => false]);

        $response = $this->get('/api/trace-utxo/invalid');

        $response->assertStatus(400);
    }
}
