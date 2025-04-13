<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\Factory as HttpClient;

final class BlockchainService
{
    private const string BASE_URL = 'https://blockstream.info/api';

    public function __construct(
        private readonly HttpClient $http,
    ) {
    }

    public function getData(string $input): ?array
    {
        return is_numeric($input)
            ? $this->getBlockData((int) $input)
            : $this->getTransactionData($input);
    }

    private function getBlockData(int $height): ?array
    {
        $hashResponse = $this->http->get(self::BASE_URL."/block-height/{$height}");
        if (!$hashResponse->successful()) {
            return null;
        }

        $hash = $hashResponse->body();

        $blockResponse = $this->http->get(self::BASE_URL."/block/{$hash}");
        $txResponse = $this->http->get(self::BASE_URL."/block/{$hash}/txs");

        if (!$blockResponse->successful() || !$txResponse->successful()) {
            return null;
        }

        $block = $blockResponse->json();
        $block['transactions'] = array_slice($txResponse->json(), 0, 5);

        return $block;
    }

    private function getTransactionData(string $txid): ?array
    {
        $txResponse = $this->http->get(self::BASE_URL."/tx/{$txid}");
        $statusResponse = $this->http->get(self::BASE_URL."/tx/{$txid}/status");

        if (!$txResponse->successful() || !$statusResponse->successful()) {
            return null;
        }

        $tx = $txResponse->json();
        $tx['status'] = $statusResponse->json();

        return $tx;
    }
}
