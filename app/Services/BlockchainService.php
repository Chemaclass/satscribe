<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\BlockchainData;
use App\Data\BlockData;
use App\Data\TransactionData;
use Illuminate\Http\Client\Factory as HttpClient;

final class BlockchainService
{
    private const string BASE_URL = 'https://blockstream.info/api';

    public function __construct(
        private readonly HttpClient $http
    ) {
    }

    public function getData(string $input): ?BlockchainData
    {
        return is_numeric($input)
            ? $this->getBlockTransfer((int) $input)
            : $this->getTransactionTransfer($input);
    }

    private function getBlockTransfer(int $height): ?BlockData
    {
        $hashRes = $this->http->get(self::BASE_URL."/block-height/{$height}");
        if (!$hashRes->successful()) {
            return null;
        }

        $hash = $hashRes->body();

        $blockRes = $this->http->get(self::BASE_URL."/block/{$hash}");
        $txsRes = $this->http->get(self::BASE_URL."/block/{$hash}/txs");

        if (!$blockRes->successful() || !$txsRes->successful()) {
            return null;
        }

        $block = $blockRes->json();
        $txs = array_slice($txsRes->json(), 0, 5);

        return new BlockData(
            hash: $block['id'],
            height: $block['height'],
            timestamp: $block['timestamp'],
            transactions: $txs
        );
    }

    private function getTransactionTransfer(string $txid): ?TransactionData
    {
        $txRes = $this->http->get(self::BASE_URL."/tx/{$txid}");
        $statusRes = $this->http->get(self::BASE_URL."/tx/{$txid}/status");

        if (!$txRes->successful() || !$statusRes->successful()) {
            return null;
        }

        $tx = $txRes->json();
        $status = $statusRes->json();

        return new TransactionData(
            txid: $tx['txid'],
            status: $status,
            version: $tx['version'],
            locktime: $tx['locktime'],
            vin: $tx['vin'],
            vout: $tx['vout'],
            size: $tx['size'],
            weight: $tx['weight'],
            fee: $tx['fee']
        );
    }
}
