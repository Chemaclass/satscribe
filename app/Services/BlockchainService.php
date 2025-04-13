<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;

final class BlockchainService
{
    public function getData(string $input): ?array
    {
        if (is_numeric($input)) {
            // Block height → hash
            $hash = Http::get("https://blockstream.info/api/block-height/{$input}")->body();
            if (!$hash) {
                return null;
            }

            $block = Http::get("https://blockstream.info/api/block/{$hash}")->json();
            $txs = Http::get("https://blockstream.info/api/block/{$hash}/txs")->json();
            $block['transactions'] = array_slice($txs, 0, 5);
            return $block;
        } else {
            $tx = Http::get("https://blockstream.info/api/tx/{$input}")->json();
            $status = Http::get("https://blockstream.info/api/tx/{$input}/status")->json();
            $tx['status'] = $status;
            return $tx;
        }
    }
}
