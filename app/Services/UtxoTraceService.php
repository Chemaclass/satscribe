<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\Factory as HttpClient;
use Psr\Log\LoggerInterface;

final readonly class UtxoTraceService
{
    private const BASE_URL = 'https://blockstream.info/api';

    public function __construct(
        private HttpClient $http,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Trace all UTXOs produced by a transaction.
     *
     * @return array<int, array{utxo: array{txid: string, vout: int, value: int}, trace: array}>
     */
    public function trace(string $txid, int $depth = 2): array
    {
        $this->logger->info('Starting UTXO trace', [
            'txid' => $txid,
            'depth' => $depth,
        ]);

        $tx = $this->getTransaction($txid);
        if (!isset($tx['vout'])) {
            $this->logger->warning('Missing vout data', ['txid' => $txid]);
            return [];
        }

        $trace = $this->traceInputs($txid, $depth, 0);

        $result = [];
        foreach ($tx['vout'] as $output) {
            $this->logger->info('Tracing output', ['output' => $output]);
            $result[] = [
                'utxo' => [
                    'txid' => $txid,
                    'vout' => $output['n'] ?? 'null',
                    'value' => $output['value'] ?? 0,
                ],
                'trace' => $trace,
            ];
        }

        return $result;
    }

    private function traceInputs(string $txid, int $depth, int $level): array
    {
        if ($depth <= 0) {
            $this->logger->info('Reached max depth', [
                'txid' => $txid,
                'level' => $level,
            ]);
            return [];
        }

        $this->logger->info('Fetching transaction', [
            'txid' => $txid,
            'level' => $level,
        ]);

        $tx = $this->getTransaction($txid);

        if (!isset($tx['vin'])) {
            $this->logger->warning('No inputs found', [
                'txid' => $txid,
                'level' => $level,
            ]);
            return [];
        }

        $inputs = [];
        foreach ($tx['vin'] as $i => $input) {
            $prevTxid = $input['txid'] ?? null;
            $vout = $input['vout'] ?? null;

            if ($prevTxid !== null && $vout !== null) {
                $value = $this->getVoutValue($prevTxid, $vout);
                $this->logger->info('Tracing input', [
                    'txid' => $prevTxid,
                    'vout' => $vout,
                    'index' => $i,
                    'level' => $level,
                    'value' => $value,
                ]);
                $inputs[] = [
                    'txid' => $prevTxid,
                    'vout' => $vout,
                    'value' => $value,
                    'source' => $this->traceInputs($prevTxid, $depth - 1, $level + 1),
                ];
            } else {
                $this->logger->warning('Missing txid or vout', [
                    'txid' => $txid,
                    'input_index' => $i,
                    'level' => $level,
                ]);
            }
        }

        return $inputs;
    }

    private function getTransaction(string $txid): array
    {
        $url = self::BASE_URL."/tx/{$txid}";
        $this->logger->info('Blockstream API call', [
            'url' => $url,
        ]);
        $response = $this->http->get($url);
        if ($response->failed()) {
            $this->logger->warning('Blockstream API error', [
                'url' => $url,
                'status' => $response->status(),
            ]);
            return [];
        }

        return $response->json();
    }

    private function getVoutValue(string $txid, int $vout): int
    {
        $tx = $this->getTransaction($txid);
        return (int) ($tx['vout'][$vout]['value'] ?? 0);
    }
}
