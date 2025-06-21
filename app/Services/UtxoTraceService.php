<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UtxoTraceRepositoryInterface;
use Psr\Log\LoggerInterface;

final readonly class UtxoTraceService
{
    private const BASE_URL = 'https://blockstream.info/api';

    public function __construct(
        private HttpClientInterface $http,
        private LoggerInterface $logger,
        private UtxoTraceRepositoryInterface $repository,
    ) {
    }

    /**
     * Same trace() response but using references to avoid repeating
     * identical child traces.
     */
    public function traceWithReferences(string $txid, int $depth = 1): array
    {
        if ($cached = $this->repository->find($txid, $depth)) {
            $this->logger->info('Loaded UTXO trace from DB', [
                'txid' => $txid,
                'depth' => $depth,
            ]);

            return $cached->result;
        }

        $result = $this->buildReferences($this->trace($txid, $depth));

        $this->repository->store($txid, $depth, $result);

        return $result;
    }

    /**
     * Trace all UTXOs produced by a transaction.
     *
     * @return array<int, array{
     *     utxo: array{
     *       txid: string,
     *       vout: int,
     *       value: int,
     *     },
     *     trace: array,
     * }>
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
                    'vout' => $output['n'] ?? null,
                    'scriptpubkey' => $output['scriptpubkey'] ?? null,
                    'scriptpubkey_address' => $output['scriptpubkey_address'] ?? null,
                    'scriptpubkey_type' => $output['scriptpubkey_type'] ?? null,
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

        return array_values(array_filter(array_map(
            fn(array $input, int $i) => $this->traceInput($input, $depth, $level, $i, $txid),
            $tx['vin'],
            array_keys($tx['vin'])
        )));
    }

    private function traceInput(array $input, int $depth, int $level, int $index, string $parentTxid): ?array
    {
        $prevTxid = $input['txid'] ?? null;
        $vout = $input['vout'] ?? null;

        if ($prevTxid === null || $vout === null) {
            $this->logger->warning('Missing txid or vout', [
                'txid' => $parentTxid,
                'input_index' => $index,
                'level' => $level,
            ]);

            return null;
        }

        $voutArray = $this->getVout($prevTxid, $vout);

        $this->logger->info('Tracing input', [
            'txid' => $prevTxid,
            'vout' => $vout,
            'index' => $index,
            'level' => $level,
            'value' => $voutArray['value'] ?? 0,
        ]);

        return [
            'txid' => $prevTxid,
            'vout' => $vout,
            'scriptpubkey' => $voutArray['scriptpubkey'] ?? null,
            'scriptpubkey_address' => $voutArray['scriptpubkey_address'] ?? null,
            'scriptpubkey_type' => $voutArray['scriptpubkey_type'] ?? null,
            'value' => $voutArray['value'] ?? 0,
            'source' => $this->traceInputs($prevTxid, $depth - 1, $level + 1),
        ];
    }

    /**
     * Convert full traces into a map of references to avoid duplication.
     */
    private function buildReferences(array $traces): array
    {
        $map = [];
        $refs = [];
        $id = 1;

        $process = static function (array $node) use (&$process, &$map, &$refs, &$id): string {
            $children = array_map($process, $node['source']);

            $key = $node['txid'].'|'.$node['vout'].'|'.$node['value'].'|'.implode(',', $children);

            if (!isset($map[$key])) {
                $ref = 'r'.$id++;
                $map[$key] = $ref;
                $refs[$ref] = [
                    'txid' => $node['txid'],
                    'vout' => $node['vout'],
                    'scriptpubkey' => $node['scriptpubkey'] ?? null,
                    'scriptpubkey_address' => $node['scriptpubkey_address'] ?? null,
                    'scriptpubkey_type' => $node['scriptpubkey_type'] ?? null,
                    'value' => $node['value'],
                    'source' => $children,
                ];
            }

            return $map[$key];
        };

        $utxos = [];

        foreach ($traces as $item) {
            $utxos[] = [
                'utxo' => $item['utxo'],
                'trace' => array_map($process, $item['trace']),
            ];
        }

        uksort($refs, static fn(string $a, string $b) => (int) substr($b, 1) <=> (int) substr($a, 1));

        return [
            'utxos' => $utxos,
            'references' => $refs,
        ];
    }

    private function getVout(string $txid, int $vout): array
    {
        return $this->getTransaction($txid)['vout'][$vout] ?? [];
    }

    private function getTransaction(string $txid): array
    {
        static $cache = [];

        if (isset($cache[$txid])) {
            return $cache[$txid];
        }

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

            return $cache[$txid] = [];
        }

        return $cache[$txid] = $response->json();
    }
}
