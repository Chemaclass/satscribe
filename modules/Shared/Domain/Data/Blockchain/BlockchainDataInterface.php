<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Data\Blockchain;

interface BlockchainDataInterface
{
    /**
     * @return array<string, mixed> blockstream-shaped payload; see the
     *         implementations for their concrete shapes
     */
    public function toArray(): array;

    public function toPrompt(): string;
}
