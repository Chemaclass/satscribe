<?php

declare(strict_types=1);

namespace Modules\UtxoTrace\Application;

use Modules\Blockchain\Domain\BlockchainServiceInterface;
use Modules\Blockchain\Domain\Data\TransactionData;
use Modules\Chat\Domain\Data\PromptInput;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class TransactionBacktraceService
{
    public function __construct(
        private BlockchainServiceInterface $blockchain,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return TransactionData[] Ordered list starting from the given tx to its ancestors.
     */
    public function getBacktrace(string $txid, int $maxDepth = 10): array
    {
        $trace = [];
        $visited = [];
        $queue = [$txid];

        while ($queue !== [] && count($trace) < $maxDepth) {
            $current = array_shift($queue);
            if (isset($visited[$current])) {
                continue;
            }

            try {
                $data = $this->blockchain->getBlockchainData(PromptInput::fromRaw($current));
                $tx = $data->transaction;
                $this->logger->info('Generating Backtrace', [
                    'count(trace)' => count($trace),
                    'maxDepth' => $maxDepth,
                    'tx' => $tx instanceof TransactionData ? $tx->txid : 'null',
                ]);
                if (!$tx instanceof TransactionData) {
                    break;
                }
            } catch (Throwable $e) {
                $this->logger->warning('Backtrace fetch failed', [
                    'txid' => $current,
                    'message' => $e->getMessage(),
                ]);
                break;
            }

            $visited[$current] = true;
            $trace[] = $tx;

            $isCoinbase = $tx->vin[0]['is_coinbase'] ?? false;
            if ($isCoinbase) {
                continue;
            }

            foreach ($tx->vin as $vin) {
                $parent = $vin['txid'] ?? null;
                if ($parent !== null && !isset($visited[$parent])) {
                    $queue[] = $parent;
                }
            }
        }

        return $trace;
    }

    /**
     * Format a list of transactions for GPT prompt usage.
     *
     * @param  TransactionData[]  $trace
     */
    public function formatForPrompt(array $trace): string
    {
        $lines = [];
        foreach ($trace as $i => $tx) {
            $lines[] = sprintf('%d. %s', $i + 1, $tx->txid);
        }

        return "Transaction Backtrace\n".implode("\n", $lines);
    }
}
