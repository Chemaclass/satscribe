<?php

declare(strict_types=1);

namespace App\Services;

final class MinerIdentifier
{
    /**
     * Map of known coinbase tags to human-friendly miner names.
     */
    private const POOLS = [
        '1THASH' => '1THash',
        '58COIN' => '58COIN & 1THash',
        'ANTPOOL' => 'AntPool',
        'BINANCE' => 'Binance Pool',
        'BINANCEPOOL' => 'Binance Pool',
        'BRAIINS' => 'Braiins Pool (Slush successor)',
        'BTC.COM' => 'BTC.com Pool',
        'BTC.TOP' => 'BTC.TOP',
        'CKPOOL' => 'Solo CKPool',
        'DPOOL' => 'DPool',
        'EASYPOOL' => 'Easy2Mine Pool',
        'EMCD' => 'EMCD.io',
        'F2POOL' => 'F2Pool',
        'FOUNDRY' => 'Foundry USA',
        'HATHOR' => 'Hathor Merge Miner',
        'HUOBI' => 'Huobi.pool',
        'KANO' => 'KanoPool',
        'KUCOIN' => 'KuCoin Pool',
        'LUXOR' => 'Luxor',
        'MARAPOOL' => 'MARA Pool',
        'MARA' => 'MARA Pool',
        'MINING-DUTCH' => 'Mining Dutch',
        'MOERO' => 'Moero Pool',
        'NICEHASH' => 'NiceHash',
        'NOVABLOCK' => 'NovaBlock',
        'OCEAN' => 'Ocean Pool',
        'OKEX' => 'OKEx Pool',
        'OKMINER' => 'OKMiner',
        'OKPOOL' => 'OKPool',
        'POOLIN' => 'Poolin',
        'RAwPOOL' => 'Rawpool',
        'SIGMAPOOL' => 'Sigma Pool',
        'SLUSH' => 'Slush Pool',
        'SPIDER' => 'SpiderPool',
        'SPIDERPOOL' => 'SpiderPool',
        'TERRAPOOL' => 'Terra Pool',
        'UUPool' => 'UUPool',
        'VIABTC' => 'ViaBTC',
        'ZPOOL' => 'Zpool',
        'ZHIZHU' => 'Zhizhu Pool',
    ];

    /**
     * Attempt to extract a known miner name from a coinbase scriptsig hex.
     *
     * @param  string  $scriptsig  Hex string of the coinbase input script
     */
    public static function extractFromCoinbaseHex(string $scriptsig): string
    {
        // Convert hex to printable ASCII
        $ascii = strtoupper((string) preg_replace('/[^[:print:]]/', '', hex2bin($scriptsig) ?: ''));

        foreach (self::POOLS as $key => $label) {
            if (str_contains($ascii, strtoupper($key))) {
                return $label;
            }
        }

        return $ascii;
    }
}
