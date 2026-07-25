<?php

declare(strict_types=1);

namespace Modules\OpenAI\Application;

use Modules\OpenAI\Domain\OfflineNarratorInterface;
use Modules\Shared\Domain\Data\Blockchain\BlockchainData;
use Modules\Shared\Domain\Data\Blockchain\BlockData;
use Modules\Shared\Domain\Data\Blockchain\TransactionData;
use Modules\Shared\Domain\Enum\Chat\PromptPersona;

use function count;
use function sprintf;

final readonly class OfflineNarrator implements OfflineNarratorInterface
{
    public function narrate(BlockchainData $data, PromptPersona $persona): string
    {
        $current = $data->current();

        if ($current instanceof BlockData) {
            $body = $this->describeBlock($current, $persona);
        } elseif ($current instanceof TransactionData) {
            $body = $this->describeTransaction($current, $persona);
        } else {
            $body = 'The requested record could not be summarised.';
        }

        return $body . "\n\n" . $this->footer();
    }

    private function describeBlock(BlockData $block, PromptPersona $persona): string
    {
        $when = $block->timestamp > 0 ? date('j F Y', $block->timestamp) : 'an unrecorded date';
        $miner = $block->coinbaseMessage !== null && $block->coinbaseMessage !== ''
            ? sprintf(' Whoever mined it left this in the coinbase: "%s".', $block->coinbaseMessage)
            : '';

        $opening = match ($persona) {
            PromptPersona::Educator => sprintf(
                'Block %s is one page in Bitcoin\'s ledger, written on %s.',
                number_format($block->height),
                $when,
            ),
            PromptPersona::Developer => sprintf(
                'Block %s, mined %s. Header version %d, nonce %d, difficulty %s.',
                number_format($block->height),
                $when,
                $block->version,
                $block->nonce,
                number_format($block->difficulty, 2),
            ),
            PromptPersona::Storyteller => sprintf(
                'On %s, block %s was sealed into the chain and has not moved since.',
                $when,
                number_format($block->height),
            ),
        };

        return $opening . ' ' . sprintf(
            'It carries %s transaction%s across %s bytes (%s weight units), and its hash begins %s.%s',
            number_format($block->txCount),
            $block->txCount === 1 ? '' : 's',
            number_format($block->size),
            number_format($block->weight),
            substr($block->hash, 0, 12),
            $miner,
        );
    }

    private function describeTransaction(TransactionData $tx, PromptPersona $persona): string
    {
        $status = $tx->confirmed
            ? sprintf('confirmed in block %s', number_format((int) $tx->blockHeight))
            : 'still waiting in the mempool';

        $opening = match ($persona) {
            PromptPersona::Educator => 'This is a single payment recorded on the Bitcoin network.',
            PromptPersona::Developer => sprintf('Transaction %s, version %d.', substr($tx->txid, 0, 12), $tx->version),
            PromptPersona::Storyteller => 'Somebody moved coins, and the network wrote it down forever.',
        };

        return $opening . ' ' . sprintf(
            'It spends %d input%s into %d output%s, weighs %s units, paid %s sats in fees, and is %s.',
            count($tx->vin),
            count($tx->vin) === 1 ? '' : 's',
            count($tx->vout),
            count($tx->vout) === 1 ? '' : 's',
            number_format($tx->weight),
            number_format($tx->fee),
            $status,
        );
    }

    /**
     * Says plainly that no model was involved. Without this the reader would
     * take a templated summary for an AI explanation, which is the whole
     * premise of the page.
     */
    private function footer(): string
    {
        return '_No AI model was reachable, so this summary was assembled directly from the '
            . 'on-chain data. Every figure above is real; none of it was written by a model. '
            . 'Pick another model above, or add your own API key, for a full explanation._';
    }
}
