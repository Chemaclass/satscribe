<?php

declare(strict_types=1);

namespace Modules\Blockchain\Application\Blockstream;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Modules\Blockchain\Domain\Exception\BlockchainException;
use Modules\Shared\Domain\Data\Blockchain\BlockchainData;
use Modules\Shared\Domain\Data\Blockchain\BlockData;
use Modules\Shared\Domain\Data\Blockchain\TransactionData;
use Modules\Shared\Domain\Data\Chat\PromptInput;
use Modules\Shared\Domain\HttpClientInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Response shapes cover only the keys this service reads; Blockstream returns
 * more, so they stay unsealed.
 *
 * @phpstan-import-type TRawBlock from BlockData
 *
 * @phpstan-type TRawTransaction array{
 *     txid: string,
 *     version: int,
 *     locktime: int,
 *     vin: list<array<string, mixed>>,
 *     vout: list<array<string, mixed>>,
 *     size: int,
 *     weight: int,
 *     fee: int,
 *     ...
 * }
 * @phpstan-type TRawTransactionStatus array{
 *     confirmed: bool,
 *     block_height?: int,
 *     block_hash?: string,
 *     block_time?: int,
 *     ...
 * }
 */
final readonly class BlockchainService
{
    private const BASE_URL = 'https://blockstream.info/api';
    private const CACHE_TTL_SECONDS = 300; // 5 minutes

    public function __construct(
        private HttpClientInterface $http,
        private CacheRepository $cache,
        private LoggerInterface $logger,
    ) {
    }

    public function getBlockchainData(PromptInput $input): BlockchainData
    {
        $cacheKey = "blockchain:{$input->type->value}:{$input->text}";

        return $this->cache->remember(
            $cacheKey,
            self::CACHE_TTL_SECONDS,
            fn () => $input->isBlock()
                ? $this->buildBlockData($input->text)
                : $this->buildTransactionData($input->text),
        );
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
                $this->fetchBlockTransactions($previousBlockHash),
            ) : null,
            $nextBlockData ? BlockData::fromArray(
                $nextBlockData,
                $this->fetchBlockTransactions($nextBlockHash),
            ) : null,
        );
    }

    private function getBlockHash(string $input): string
    {
        if (!is_numeric($input)) {
            return $input;
        }

        try {
            $response = $this->http->get(self::BASE_URL . "/block-height/{$input}");
        } catch (Throwable $e) {
            $this->logger->error('Block height lookup error', ['height' => $input, 'error' => $e->getMessage()]);
            throw BlockchainException::blockOrTxFetchFailed($input);
        }

        if (!$response->successful()) {
            $this->logger->warning('Block height lookup failed', ['height' => $input]);
            throw BlockchainException::blockOrTxFetchFailed($input);
        }

        return $response->body();
    }

    /**
     * @return TRawBlock
     */
    private function fetchBlock(string $hash): array
    {
        try {
            $response = $this->http->get(self::BASE_URL . "/block/{$hash}");
        } catch (Throwable $e) {
            $this->logger->error('Block fetch error', ['hash' => $hash, 'error' => $e->getMessage()]);
            throw BlockchainException::blockOrTxFetchFailed($hash);
        }

        if (!$response->successful()) {
            $this->logger->warning('Block fetch failed', ['hash' => $hash]);
            throw BlockchainException::blockOrTxFetchFailed($hash);
        }

        /** @var TRawBlock $block */
        $block = BlockstreamPayload::object($response->json(), "block {$hash}", [
            'id' => BlockstreamPayload::STRING,
            'height' => BlockstreamPayload::INT,
            'version' => BlockstreamPayload::INT,
            'timestamp' => BlockstreamPayload::INT,
            'tx_count' => BlockstreamPayload::INT,
            'size' => BlockstreamPayload::INT,
            'weight' => BlockstreamPayload::INT,
            'merkle_root' => BlockstreamPayload::STRING,
        ]);

        return $block;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchBlockTransactions(string $hash): array
    {
        try {
            $response = $this->http->get(self::BASE_URL . "/block/{$hash}/txs");
        } catch (Throwable $e) {
            $this->logger->error('Block transactions fetch error', ['hash' => $hash, 'error' => $e->getMessage()]);
            throw BlockchainException::blockOrTxFetchFailed($hash);
        }

        if (!$response->successful()) {
            $this->logger->warning('Block transactions fetch failed', ['hash' => $hash]);
            throw BlockchainException::blockOrTxFetchFailed($hash);
        }

        return BlockstreamPayload::objectList($response->json(), "transactions of block {$hash}");
    }

    /**
     * @param  TRawBlock  $block
     */
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

    /**
     * @return TRawTransaction
     */
    private function fetchTransaction(string $txid): array
    {
        try {
            $response = $this->http->get(self::BASE_URL . "/tx/{$txid}");
        } catch (Throwable $e) {
            $this->logger->error('Transaction fetch error', ['txid' => $txid, 'error' => $e->getMessage()]);
            throw BlockchainException::txLookupFailed($txid);
        }

        if (!$response->successful()) {
            $this->logger->warning('Transaction fetch failed', ['txid' => $txid]);
            throw BlockchainException::txLookupFailed($txid);
        }

        /** @var TRawTransaction $tx */
        $tx = BlockstreamPayload::object($response->json(), "transaction {$txid}", [
            'txid' => BlockstreamPayload::STRING,
            'version' => BlockstreamPayload::INT,
            'locktime' => BlockstreamPayload::INT,
            'vin' => BlockstreamPayload::ARRAY,
            'vout' => BlockstreamPayload::ARRAY,
            'size' => BlockstreamPayload::INT,
            'weight' => BlockstreamPayload::INT,
            'fee' => BlockstreamPayload::INT,
        ]);

        return $tx;
    }

    /**
     * @return TRawTransactionStatus
     */
    private function fetchTransactionStatus(string $txid): array
    {
        try {
            $response = $this->http->get(self::BASE_URL . "/tx/{$txid}/status");
        } catch (Throwable $e) {
            $this->logger->error('Transaction status fetch error', ['txid' => $txid, 'error' => $e->getMessage()]);
            throw BlockchainException::txLookupFailed($txid);
        }

        if (!$response->successful()) {
            $this->logger->warning('Transaction status fetch failed', ['txid' => $txid]);
            throw BlockchainException::txLookupFailed($txid);
        }

        // Only `confirmed` is guaranteed; the block_* fields are absent for a
        // transaction still in the mempool.
        /** @var TRawTransactionStatus $status */
        $status = BlockstreamPayload::object($response->json(), "status of {$txid}", [
            'confirmed' => BlockstreamPayload::BOOL,
        ]);

        return $status;
    }
}
