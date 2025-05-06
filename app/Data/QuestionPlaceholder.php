<?php
declare(strict_types=1);

namespace App\Data;

final class QuestionPlaceholder
{
    public const SAMPLE_QUESTIONS = [
        'transaction' => [
            "What is the fee and fee rate?",
            "Is this a CoinJoin or a batch transaction?",
            "Does it use Taproot or SegWit?",
            "Is it a self-transfer between wallets?",
            "How many inputs and outputs are used?",
            "Was Replace-By-Fee (RBF) used?",
            "Does the transaction use multi-signature addresses?",
            "What is the estimated USD value transferred?",
            "Is this transaction related to a known address or service?",
            "Does this transaction consolidate UTXOs?",
            "Is this a typical example of a Bitcoin transaction?",
        ],
        'block' => [
            "Which pool mined this block?",
            "How many transactions are inside?",
            "How big is this block and how fast was it mined?",
            "Are there any Ordinals or inscriptions?",
            "Was the block filled to capacity?",
            "What is the coinbase message?",
            "What is the reward earned by the miner?",
            "Does this block signal support for any soft forks?",
            "Are there any empty or low-fee transactions?",
            "What was the difficulty at this block height?",
            "Is this a typical example of a Bitcoin block?",
        ],
        'both' => [
            "Explain this like I'm five.",
            "What's unusual or interesting here?",
            "Write a short, simple summary.",
            "Why might someone be analyzing this?",
            "Summarize this as if for a news article headline.",
            "What can we learn from this data?",
            "How does this fit into the big picture of Bitcoin?",
            "Can you identify any trends or anomalies?",
            "What is the educational value of this?",
        ],
    ];

    public static function rand(): string
    {
        return collect(self::SAMPLE_QUESTIONS)->flatten()->random();
    }

    /**
     * @return list<string>
     */
    public static function forBlock(): array
    {
        return collect([
            ...self::SAMPLE_QUESTIONS['block'],
            ...self::SAMPLE_QUESTIONS['both'],
        ])->shuffle()->take(5)->values()->all();
    }

    /**
     * @return list<string>
     */
    public static function forTx(): array
    {
        return collect([
            ...self::SAMPLE_QUESTIONS['transaction'],
            ...self::SAMPLE_QUESTIONS['both'],
        ])->shuffle()->take(5)->values()->all();
    }

    /**
     * @return list<string>
     */
    public static function groupedPrompts(): array
    {
        return collect(self::SAMPLE_QUESTIONS)
            ->map(fn(array $prompts) => collect($prompts)->shuffle()->take(3))
            ->all();
    }
}
