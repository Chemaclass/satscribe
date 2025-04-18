<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\BlockchainData;
use App\Data\BlockData;
use App\Data\TransactionData;
use App\Exceptions\BlockchainException;
use Illuminate\Http\Client\Factory as HttpClient;
use Psr\Log\LoggerInterface;

final readonly class BlockchainService
{
    private const BASE_URL = 'https://blockstream.info/api';

    public function __construct(
        private HttpClient $http,
        private LoggerInterface $logger,
    ) {
    }

    public function getBlockchainData(string $input): BlockchainData
    {
        return $this->isBlock($input)
            ? $this->getBlockData($input)
            : $this->getTransactionData($input);
    }

    private function getBlockData(string $input): BlockData
    {
        $hash = $this->getBlockHash($input);

        $blockRes = $this->http->get(self::BASE_URL."/block/{$hash}");
        $txsRes = $this->http->get(self::BASE_URL."/block/{$hash}/txs");

        if (!$blockRes->successful() || !$txsRes->successful()) {
            $this->logger->warning('Block or transactions fetch failed', ['hash' => $hash]);
            throw BlockchainException::blockOrTxFetchFailed($hash);
        }

        $block = $blockRes->json();
        $txs = $txsRes->json();

        $this->logger->info('Fetched block data', ['block' => $block]);
        $this->logger->info('Fetched block transactions', ['transactions' => $txs]);

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

    private function getTransactionData(string $txid): TransactionData
    {
        $txRes = $this->http->get(self::BASE_URL."/tx/{$txid}");
        $statusRes = $this->http->get(self::BASE_URL."/tx/{$txid}/status");

        if (!$txRes->successful() || !$statusRes->successful()) {
            $this->logger->warning('Transaction lookup failed', ['txid' => $txid]);
            throw BlockchainException::txLookupFailed($txid);
        }

        $tx = $txRes->json();
        $status = $statusRes->json();

        $this->logger->info('Fetched transaction data', ['transaction' => $tx]);
        $this->logger->info('Fetched transaction status', ['status' => $status]);

        return new TransactionData(
            txid: $tx['txid'],
            version: $tx['version'],
            locktime: $tx['locktime'],
            vin: $tx['vin'],
            vout: $tx['vout'],
            size: $tx['size'],
            weight: $tx['weight'],
            fee: $tx['fee'],
            confirmed: $status['confirmed'],
            blockHeight: $status['block_height'] ?? null,
            blockHash: $status['block_hash'] ?? null,
            blockTime: $status['block_time'] ?? null,
        );
    }

    private function isBlock(string $input): bool
    {
        return is_numeric($input)
            || preg_match('/^0{8,}[a-f0-9]{56}$/i', $input);
    }

    private function getBlockHash(string $input): string
    {
        if (!is_numeric($input)) {
            return $input;
        }

        $hashRes = $this->http->get(self::BASE_URL."/block-height/{$input}");
        if (!$hashRes->successful()) {
            $this->logger->warning('Block height lookup failed', [
                'height' => $input,
                'response' => $hashRes->body(),
            ]);
            return $input;
        }

        return $hashRes->body();
    }
}
