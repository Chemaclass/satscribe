<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\Blockchain\BlockchainData;
use App\Data\Blockchain\BlockData;
use App\Data\Blockchain\TransactionData;
use App\Data\PromptInput;
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

    public function getBlockchainData(PromptInput $input): BlockchainData
    {
        return $input->isBlock()
            ? $this->buildBlockData($input->text)
            : $this->buildTransactionData($input->text);
    }

    private function buildBlockData(string $input): BlockchainData
    {
        $hash = $this->getBlockHash($input);

        $block = $this->fetchBlock($hash);
        $txs = $this->fetchBlockTransactions($hash);

        $previousBlock = $block['previousblockhash'] ?? null;
        $previousBlockData = $previousBlock ? $this->fetchBlock($previousBlock) : null;

        $nextBlockHash = $this->fetchNextBlockHash($block);
        $nextBlockData = $nextBlockHash ? $this->fetchBlock($nextBlockHash) : null;

        return BlockchainData::forBlock(
            BlockData::fromArray($block, $txs),
            $previousBlockData ? BlockData::fromArray($previousBlockData) : null,
            $nextBlockData ? BlockData::fromArray($nextBlockData) : null,
        );
    }

    private function buildTransactionData(string $txid): BlockchainData
    {
        $tx = $this->fetchTransaction($txid);
        $status = $this->fetchTransactionStatus($txid);

        $blockData = null;
        if (!empty($status['block_hash'])) {
            $block = $this->fetchBlock($status['block_hash']);
            $blockData = BlockData::fromArray($block);
        }

        return BlockchainData::forTransaction(
            new TransactionData(
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
            ),
            $blockData,
        );
    }

    private function fetchBlock(string $hash): array
    {
        $response = $this->http->get(self::BASE_URL."/block/{$hash}");
        if (!$response->successful()) {
            $this->logger->warning('Block fetch failed', ['hash' => $hash]);
            throw BlockchainException::blockOrTxFetchFailed($hash);
        }
        return $response->json();
    }

    private function fetchBlockTransactions(string $hash): array
    {
        $response = $this->http->get(self::BASE_URL."/block/{$hash}/txs");
        if (!$response->successful()) {
            $this->logger->warning('Block transactions fetch failed', ['hash' => $hash]);
            throw BlockchainException::blockOrTxFetchFailed($hash);
        }
        return $response->json();
    }

    private function fetchNextBlockHash(array $block): ?string
    {
        try {
            return $this->getBlockHash((string) ($block['height'] + 1));
        } catch (BlockchainException) {
            return null;
        }
    }

    private function fetchTransaction(string $txid): array
    {
        $response = $this->http->get(self::BASE_URL."/tx/{$txid}");
        if (!$response->successful()) {
            $this->logger->warning('Transaction fetch failed', ['txid' => $txid]);
            throw BlockchainException::txLookupFailed($txid);
        }
        return $response->json();
    }

    private function fetchTransactionStatus(string $txid): array
    {
        $response = $this->http->get(self::BASE_URL."/tx/{$txid}/status");
        if (!$response->successful()) {
            $this->logger->warning('Transaction status fetch failed', ['txid' => $txid]);
            throw BlockchainException::txLookupFailed($txid);
        }
        return $response->json();
    }

    private function getBlockHash(string $input): string
    {
        if (!is_numeric($input)) {
            return $input;
        }
        $response = $this->http->get(self::BASE_URL."/block-height/{$input}");
        if (!$response->successful()) {
            $this->logger->warning('Block height lookup failed', ['height' => $input]);
            throw BlockchainException::blockOrTxFetchFailed($input);
        }
        return $response->body();
    }
}
