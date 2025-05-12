<?php
declare(strict_types=1);

namespace App\Data\Blockchain;

use App\Data\BlockchainDataInterface;
use App\Models\Message;

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

    public static function fromMessage(Message $message): self
    {
        $data = data_get($message->meta, 'raw_data');

        $transaction = null;
        $block = null;

        // Detect transaction data and instantiate
        if (isset($data['txid'])) {
            $transaction = new TransactionData(
                txid: $data['txid'],
                version: $data['version'],
                locktime: $data['locktime'],
                vin: $data['vin'],
                vout: $data['vout'],
                size: $data['size'],
                weight: $data['weight'],
                fee: $data['fee'],
                confirmed: $data['status']['confirmed'],
                blockHeight: $data['status']['block_height'] ?? null,
                blockHash: $data['status']['block_hash'] ?? null,
                blockTime: $data['status']['block_time'] ?? null,
            );
        }

        // Detect block data and instantiate
        if (isset($data['block'])) {
            $blockData = $data['block'];
            $block = BlockData::fromArray(
                data: $blockData,
                transactions: $blockData['transactions'] ?? []
            );
        }

        return new self(
            block: $block,
            transaction: $transaction,
            previousBlock: null,
            nextBlock: null,
            transactionBlock: $block // reuse if tx and block are related
        );
    }

    public function current(): BlockchainDataInterface
    {
        if ($this->transaction instanceof TransactionData) {
            return $this->transaction;
        }

        return $this->block;
    }

    public static function forBlock(BlockData $block, ?BlockData $previous = null, ?BlockData $next = null): self
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

            $sections[] = "---\nTX Block Summary\n";
            $sections[] = $this->transactionBlock?->toPrompt() ?? 'Block not found';
        }

        return implode("\n\n", $sections);
    }
}
