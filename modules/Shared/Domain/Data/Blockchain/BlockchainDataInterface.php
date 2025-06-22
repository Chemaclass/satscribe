<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Data\Blockchain;

interface BlockchainDataInterface
{
    public function getType(): string;

    public function getInput(): string;

    public function toArray(): array;

    public function toPrompt(): string;
}
