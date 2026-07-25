<?php

declare(strict_types=1);

namespace Modules\UtxoTrace\Application\Tracer;

use App\Models\UtxoTrace;
use Modules\Shared\Domain\HttpClientInterface;
use Modules\UtxoTrace\Domain\Repository\UtxoTraceRepositoryInterface;
use Modules\UtxoTrace\Domain\UtxoTraceFacadeInterface;
use Psr\Log\LoggerInterface;

/**
 * Shapes below are derived from how this class reads the Blockstream
 * `/tx/{txid}` response, not from the full API contract, so the external
 * payload shapes are left unsealed.
 *
 * `TTraceNode['source']` is a `list<TTraceNode>`; PHPStan rejects recursive
 * type aliases, so it is widened at that one point only.
 *
 * Esplora `vout` objects carry no index field of their own — an output is
 * identified by its position in the array, which is what `vin[].vout` refers to.
 *
 * @phpstan-type TVout array{
 *     scriptpubkey?: string,
 *     scriptpubkey_address?: string,
 *     scriptpubkey_type?: string,
 *     value?: int,
 *     ...
 * }
 * @phpstan-type TTransaction array{
 *     vin?: array<int, array<string, mixed>>,
 *     vout?: array<int, TVout>,
 *     ...
 * }
 * @phpstan-type TTraceNode array{
 *     txid: string,
 *     vout: int,
 *     scriptpubkey: string|null,
 *     scriptpubkey_address: string|null,
 *     scriptpubkey_type: string|null,
 *     value: int,
 *     source: list<array<string, mixed>>,
 * }
 * @phpstan-type TUtxoEntry array{
 *     utxo: array{
 *         txid: string,
 *         vout: int,
 *         scriptpubkey: string|null,
 *         scriptpubkey_address: string|null,
 *         scriptpubkey_type: string|null,
 *         value: int,
 *     },
 *     trace: list<TTraceNode>,
 * }
 *
 * @phpstan-import-type TReferencedTrace from UtxoTraceFacadeInterface
 */
final readonly class UtxoTracer
{
    private const BASE_URL = 'https://blockstream.info/api';

    public function __construct(
        private HttpClientInterface $http,
        private LoggerInterface $logger,
        private UtxoTraceRepositoryInterface $repository,
    ) {
    }

    /**
     * Same response as buildBacktrace() but using references to avoid
     * repeating identical child traces.
     *
     * Cached rows replay a previously stored TReferencedTrace, but Eloquent's
     * JSON cast returns mixed, so that arm is not statically provable.
     *
     * @return TReferencedTrace
     */
    public function getBacktrace(string $txid, int $depth = 1): array
    {
        if (($cached = $this->repository->find($txid, $depth)) instanceof UtxoTrace) {
            $this->logger->debug('Loaded UTXO trace from DB', [
                'txid' => $txid,
                'depth' => $depth,
            ]);

            // store() only ever persists a TReferencedTrace, but Eloquent's
            // JSON cast erases that to mixed on the way back out.
            /** @var TReferencedTrace $result */
            $result = $cached->result;

            return $result;
        }

        $result = $this->buildReferences($this->buildBacktrace($txid, $depth));

        $this->repository->store($txid, $depth, $result);

        return $result;
    }

    /**
     * Trace all UTXOs produced by a transaction.
     *
     * @return list<TUtxoEntry>
     */
    public function buildBacktrace(string $txid, int $depth = 2): array
    {
        $this->logger->debug('Starting UTXO trace', [
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
        foreach ($tx['vout'] as $index => $output) {
            $this->logger->debug('Tracing output', ['output' => $output]);
            $result[] = [
                'utxo' => [
                    'txid' => $txid,
                    // The position in the array *is* the vout number; Esplora
                    // ships no index field inside the output object.
                    'vout' => $index,
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

    /**
     * Convert full traces into a map of references to avoid duplication.
     *
     * @param  list<TUtxoEntry>  $traces
     *
     * @return TReferencedTrace
     */
    private function buildReferences(array $traces): array
    {
        $map = [];
        $refs = [];
        $id = 1;

        $process = static function (array $node) use (&$process, &$map, &$refs, &$id): string {
            $children = array_map($process, $node['source']);

            $key = $node['txid'] . '|' . $node['vout'] . '|' . $node['value'] . '|' . implode(',', $children);

            if (!isset($map[$key])) {
                $ref = 'r' . $id++;
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

        uksort($refs, static fn (string $a, string $b) => (int) substr($b, 1) <=> (int) substr($a, 1));

        return [
            'utxos' => $utxos,
            'references' => $refs,
        ];
    }

    /**
     * @return TTransaction empty when the API call fails
     */
    private function getTransaction(string $txid): array
    {
        static $cache = [];

        if (isset($cache[$txid])) {
            return $cache[$txid];
        }

        $url = self::BASE_URL . "/tx/{$txid}";
        $this->logger->debug('Blockstream API call', [
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

    /**
     * @return list<TTraceNode>
     */
    private function traceInputs(string $txid, int $depth, int $level): array
    {
        if ($depth <= 0) {
            $this->logger->debug('Reached max depth', [
                'txid' => $txid,
                'level' => $level,
            ]);
            return [];
        }

        $this->logger->debug('Fetching transaction', [
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
            fn (array $input, int $i) => $this->traceInput($input, $depth, $level, $i, $txid),
            $tx['vin'],
            array_keys($tx['vin']),
        )));
    }

    /**
     * @param  array<string, mixed>  $input  a Blockstream `vin` entry
     *
     * @return TTraceNode|null null when the input lacks a txid or vout
     */
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

        $this->logger->debug('Tracing input', [
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
     * @return TVout empty when the transaction has no such output
     */
    private function getVout(string $txid, int $vout): array
    {
        return $this->getTransaction($txid)['vout'][$vout] ?? [];
    }
}
