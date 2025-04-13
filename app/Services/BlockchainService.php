<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\BlockchainData;
use App\Data\BlockData;
use App\Data\TransactionData;
use Illuminate\Http\Client\Factory as HttpClient;

final readonly class BlockchainService
{
    private const string BASE_URL = 'https://blockstream.info/api';
    private const int TX_LIMIT = 5;

    public function __construct(
        private HttpClient $http,
    ) {
    }

    public function getBlockchainData(string $input): ?BlockchainData
    {
        return is_numeric($input)
            ? $this->getBlockTransfer((int) $input)
            : $this->getTransactionTransfer($input);
    }

    private function getBlockTransfer(int $height): ?BlockData
    {
        $hashRes = $this->http->get(self::BASE_URL."/block-height/{$height}");
        if (!$hashRes->successful()) {
            // todo: log error
            return null;
        }

        $hash = $hashRes->body();

        $blockRes = $this->http->get(self::BASE_URL."/block/{$hash}");
        $txsRes = $this->http->get(self::BASE_URL."/block/{$hash}/txs");

        if (!$blockRes->successful() || !$txsRes->successful()) {
            // todo: log error
            return null;
        }

        $block = $blockRes->json();
        $txs = array_slice($txsRes->json(), 0, self::TX_LIMIT);

        return new BlockData(
            hash: $block['id'],
            height: $block['height'],
            timestamp: $block['timestamp'],
            totalTransactions: count($txsRes->json()),
            transactions: $txs,
        );
    }

    private function getTransactionTransfer(string $txid): ?TransactionData
    {
        $txRes = $this->http->get(self::BASE_URL."/tx/{$txid}");
        $statusRes = $this->http->get(self::BASE_URL."/tx/{$txid}/status");

        if (!$txRes->successful() || !$statusRes->successful()) {
            // todo: log error
            return null;
        }

        $tx = $txRes->json();

        return new TransactionData(
            txid: $tx['txid'],
            status: $statusRes->json(),
            version: $tx['version'],
            locktime: $tx['locktime'],
            vin: $tx['vin'],
            vout: $tx['vout'],
            size: $tx['size'],
            weight: $tx['weight'],
            fee: $tx['fee'],
        );
    }
}
