<?php
declare(strict_types=1);

namespace App\Data\Blockchain;

use App\Data\BlockchainDataInterface;

final readonly class BlockchainData
{
    private function __construct(
        public ?BlockData $block,
        public ?TransactionData $transaction,
        public ?BlockData $previousBlock,
        public ?BlockData $nextBlock,
        public ?BlockData $transactionBlock,
    ) {
    }

    public function current(): BlockchainDataInterface
    {
        if ($this->transaction instanceof TransactionData) {
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

        if ($this->block instanceof BlockData) {
            $sections[] = $this->block->toPrompt();

            if ($this->previousBlock instanceof BlockData) {
                $sections[] = "---\nPrevious Block Summary\n";
                $sections[] = $this->previousBlock->toPrompt();
            }

            if ($this->nextBlock instanceof BlockData) {
                $sections[] = "---\nNext Block Summary\n";
                $sections[] = $this->nextBlock->toPrompt();
            }
        }

        if ($this->transaction instanceof TransactionData) {
            $sections[] = $this->transaction->toPrompt();
        }

        return implode("\n\n", $sections);
    }
}
