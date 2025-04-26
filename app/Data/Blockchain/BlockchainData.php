<?php
declare(strict_types=1);

namespace App\Data\Blockchain;

use App\Data\BlockchainDataInterface;

final class BlockchainData
{
    private function __construct(
        public readonly ?BlockData $block,
        public readonly ?TransactionData $transaction,
        public readonly ?BlockData $previousBlock,
        public readonly ?BlockData $nextBlock,
        public readonly ?BlockData $transactionBlock,
    ) {
    }

    public function current(): BlockchainDataInterface
    {
        if ($this->transaction !== null) {
            return $this->transaction;
        }

        return $this->block;
    }

    public static function forBlock(BlockData $block, BlockData $previous, ?BlockData $next = null): self
    {
        return new self(
            block: $block,
            transaction: null,
            previousBlock: $previous,
            nextBlock: $next,
            transactionBlock: null,
        );
    }

    public static function forTransaction(TransactionData $tx, ?BlockData $block = null): self
    {
        return new self(
            block: null,
            transaction: $tx,
            previousBlock: null,
            nextBlock: null,
            transactionBlock: $block
        );
    }

    public function toPrompt(): string
    {
        $sections = [];

        if ($this->block) {
            $sections[] = $this->block->toPrompt();

            if ($this->previousBlock) {
                $sections[] = "---\nPrevious Block Summary\n";
                $sections[] = $this->previousBlock->toPrompt();
            }

            if ($this->nextBlock) {
                $sections[] = "---\nNext Block Summary\n";
                $sections[] = $this->nextBlock->toPrompt();
            }
        }

        if ($this->transaction) {
            $sections[] = $this->transaction->toPrompt();
        }

        return implode("\n\n", $sections);
    }
}
