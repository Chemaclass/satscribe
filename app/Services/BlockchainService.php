<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\BlockchainData;
use App\Data\BlockData;
use App\Data\TransactionData;
use Illuminate\Http\Client\Factory as HttpClient;
use Psr\Log\LoggerInterface;

final readonly class BlockchainService
{
    private const string BASE_URL = 'https://blockstream.info/api';
    private const int TX_LIMIT = 10;

    public function __construct(
        private HttpClient $http,
        private LoggerInterface $logger,
    ) {
    }

    public function getBlockchainData(string $input): ?BlockchainData
    {
        return is_numeric($input)
            ? $this->getBlockData((int) $input)
            : $this->getTransactionData($input);
    }

    private function getBlockData(int $height): ?BlockData
    {
        $hashRes = $this->http->get(self::BASE_URL."/block-height/{$height}");
        if (!$hashRes->successful()) {
            $this->logger->warning('Block height lookup failed', [
                'height' => $height,
                'response' => $hashRes->body(),
            ]);
            return null;
        }

        $hash = $hashRes->body();

        $blockRes = $this->http->get(self::BASE_URL."/block/{$hash}");
        $txsRes = $this->http->get(self::BASE_URL."/block/{$hash}/txs");

        if (!$blockRes->successful() || !$txsRes->successful()) {
            $this->logger->warning('Block or transactions fetch failed', ['hash' => $hash]);
            return null;
        }

        $block = $blockRes->json();
        $txs = $txsRes->json();

        $this->logger->info("Fetched block data:\n".json_encode($block, JSON_PRETTY_PRINT));
        $this->logger->info("Fetched block transactions:\n".json_encode($txs, JSON_PRETTY_PRINT));

        return new BlockData(
            hash: $block['id'],
            height: $block['height'],
            version: $block['version'],
            timestamp: $block['timestamp'],
            txCount: $block['tx_count'],
            size: $block['size'],
            weight: $block['weight'],
            merkleRoot: $block['merkle_root'],
            previousBlockHash: $block['previousblockhash'],
            medianTime: $block['mediantime'],
            nonce: $block['nonce'],
            bits: $block['bits'],
            difficulty: $block['difficulty'],
            transactions: $txs,
        );
    }

    private function getTransactionData(string $txid): ?TransactionData
    {
        $txRes = $this->http->get(self::BASE_URL."/tx/{$txid}");
        $statusRes = $this->http->get(self::BASE_URL."/tx/{$txid}/status");

        if (!$txRes->successful() || !$statusRes->successful()) {
            $this->logger->warning('Transaction lookup failed', ['txid' => $txid]);
            return null;
        }

        $tx = $txRes->json();
        $status = $statusRes->json();

        $this->logger->info("Fetched transaction data:\n".json_encode($tx, JSON_PRETTY_PRINT));
        $this->logger->info("Fetched transaction status:\n".json_encode($status, JSON_PRETTY_PRINT));

        return new TransactionData(
            txid: $tx['txid'],
            status: $status,
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
