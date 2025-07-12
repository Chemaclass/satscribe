<?php

declare(strict_types=1);

namespace Modules\Blockchain\Domain;

interface PriceServiceInterface
{
    public function getCurrentBtcPriceUsd(): float;

    public function getCurrentBtcPriceEur(): float;

    public function getCurrentBtcPriceCny(): float;

    public function getCurrentBtcPriceGbp(): float;

    public function getBtcPriceUsdAt(int $timestamp): float;

    public function getBtcPriceEurAt(int $timestamp): float;
}
