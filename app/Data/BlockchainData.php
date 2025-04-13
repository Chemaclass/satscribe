<?php

namespace App\Data;

interface BlockchainData
{
    public function getType(): string;

    public function getInput(): string;

    public function toArray(): array;
}
