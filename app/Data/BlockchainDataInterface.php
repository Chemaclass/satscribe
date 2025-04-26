<?php

namespace App\Data;

interface BlockchainDataInterface
{
    public function getType(): string;

    public function getInput(): string;

    public function toArray(): array;

    public function toPrompt(): string;
}
