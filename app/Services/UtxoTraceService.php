<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\Factory as HttpClient;
use Psr\Log\LoggerInterface;
use App\Repositories\UtxoTraceRepositoryInterface;

final readonly class UtxoTraceService
{
    private const BASE_URL = 'https://blockstream.info/api';

    public function __construct(
        private HttpClient $http,
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

        $traces = $this->trace($txid, $depth);

        $map = [];
        $refs = [];
        $id = 1;

        $process = static function (array $node) use (&$process, &$map, &$refs, &$id): string {
            $children = [];
            foreach ($node['source'] as $child) {
                $children[] = $process($child);
            }
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

        $result = ['utxos' => [], 'references' => []];

        foreach ($traces as $item) {
            $traceRefs = [];
            foreach ($item['trace'] as $child) {
                $traceRefs[] = $process($child);
            }
            $result['utxos'][] = [
                'utxo' => $item['utxo'],
                'trace' => $traceRefs,
            ];
        }

        uksort($refs, fn(string $a, string $b) => (int) substr($b, 1) <=> (int) substr($a, 1));
        $result['references'] = $refs;

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

        $inputs = [];
        foreach ($tx['vin'] as $i => $input) {
            $prevTxid = $input['txid'] ?? null;
            $vout = $input['vout'] ?? null;

            if ($prevTxid !== null && $vout !== null) {
                $voutArray = $this->getVout($prevTxid, $vout);

                $this->logger->info('Tracing input', [
                    'txid' => $prevTxid,
                    'vout' => $vout,
                    'index' => $i,
                    'level' => $level,
                    'value' => $voutArray['value'],
                ]);
                $inputs[] = [
                    'txid' => $prevTxid,
                    'vout' => $vout,
                    'scriptpubkey' => $voutArray['scriptpubkey'] ?? null,
                    'scriptpubkey_address' => $voutArray['scriptpubkey_address'] ?? null,
                    'scriptpubkey_type' => $voutArray['scriptpubkey_type'] ?? null,
                    'value' => $voutArray['value'],
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

    private function getVout(string $txid, int $vout): array|null
    {
        return $this->getTransaction($txid)['vout'][$vout];
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
}
