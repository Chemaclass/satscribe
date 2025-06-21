<?php

declare(strict_types=1);

namespace Modules\Blockchain\Application\Blockstream;

use Modules\Blockchain\Domain\Data\BlockchainData;
use Modules\Blockchain\Domain\Data\BlockData;
use Modules\Blockchain\Domain\Data\TransactionData;
use Modules\Blockchain\Domain\Exception\BlockchainException;
use Modules\Chat\Domain\Data\PromptInput;
use Modules\Shared\Domain\HttpClientInterface;
use Psr\Log\LoggerInterface;

final readonly class BlockchainService
{
    private const BASE_URL = 'https://blockstream.info/api';

    public function __construct(
        private HttpClientInterface $http,
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

        $rawBlock = $this->fetchBlock($hash);
        $txs = $this->fetchBlockTransactions($hash);

        $previousBlockHash = $rawBlock['previousblockhash'] ?? null;
        $previousBlockData = $previousBlockHash ? $this->fetchBlock($previousBlockHash) : null;

        $nextBlockHash = $this->fetchNextBlockHash($rawBlock);
        $nextBlockData = $nextBlockHash ? $this->fetchBlock($nextBlockHash) : null;

        return BlockchainData::forBlock(
            BlockData::fromArray($rawBlock, $txs),
            $previousBlockData ? BlockData::fromArray(
                $previousBlockData,
                $this->fetchBlockTransactions($previousBlockHash)
            ) : null,
            $nextBlockData ? BlockData::fromArray(
                $nextBlockData,
                $this->fetchBlockTransactions($nextBlockHash)
            ) : null,
        );
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

    private function buildTransactionData(string $txid): BlockchainData
    {
        $rawTx = $this->fetchTransaction($txid);
        $rawStatusTx = $this->fetchTransactionStatus($txid);

        $blockData = null;
        if (!empty($rawStatusTx['block_hash'])) {
            $rawBlock = $this->fetchBlock($rawStatusTx['block_hash']);
            $blockData = BlockData::fromArray($rawBlock);
        }

        return BlockchainData::forTransaction(
            new TransactionData(
                txid: $rawTx['txid'],
                version: $rawTx['version'],
                locktime: $rawTx['locktime'],
                vin: $rawTx['vin'],
                vout: $rawTx['vout'],
                size: $rawTx['size'],
                weight: $rawTx['weight'],
                fee: $rawTx['fee'],
                confirmed: $rawStatusTx['confirmed'],
                blockHeight: $rawStatusTx['block_height'] ?? null,
                blockHash: $rawStatusTx['block_hash'] ?? null,
                blockTime: $rawStatusTx['block_time'] ?? null,
            ),
            $blockData,
        );
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
}
