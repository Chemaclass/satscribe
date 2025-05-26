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
            "Does this transaction consolidate UTXOs?",
            "What is the total output value?",
            "How long did it take to confirm?",
            "Is there a time lock or script involved?",
            "How many confirmations does it currently have?",
            "Is the input value significantly higher than the output value?",
        ],
        'block' => [
            "Which pool mined this block?",
            "How many transactions are inside?",
            "How fast was it mined?",
            "Are there any Ordinals or inscriptions?",
            "Was the block filled to capacity?",
            "What is the coinbase message?",
            "What is the reward earned by the miner?",
            "What was the difficulty at this block height?",
            "How many satoshis per vByte was the median fee?",
            "Is there a spike in transaction volume compared to previous blocks?",
            "Did the miner include any zero-fee transactions?",
            "Are there any OP_RETURN outputs in this block?",
            "Are there any transactions spending very old coins?",
            "Did the block include high-fee transactions?",
        ],
        'both' => [
            "Explain this like I'm five.",
            "What's unusual or interesting here?",
            "Write a short, simple summary.",
            "Can you identify any trends or anomalies?",
            "Summarize this in a tweet.",
            "Is there anything suspicious or out of the ordinary?",
            "Tell me something interesting I might miss at first glance.",
            "Generate a title for a blog post about this.",
        ],
    ];

    public static function rand(): string
    {
        return __(collect(self::SAMPLE_QUESTIONS)->flatten()->random());
    }

    /**
     * @return list<string>
     */
    public static function forBlock(): array
    {
        $blockPrompts = collect(self::SAMPLE_QUESTIONS['block'])->shuffle()->take(2);
        $bothPrompts = collect(self::SAMPLE_QUESTIONS['both'])->shuffle()->take(1);

        return $blockPrompts
            ->merge($bothPrompts)
            ->shuffle()
            ->map(fn(string $prompt) => __($prompt))
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public static function forTx(): array
    {
        $blockPrompts = collect(self::SAMPLE_QUESTIONS['transaction'])->shuffle()->take(2);
        $bothPrompts = collect(self::SAMPLE_QUESTIONS['both'])->shuffle()->take(1);

        return $blockPrompts
            ->merge($bothPrompts)
            ->shuffle()
            ->map(fn(string $prompt) => __($prompt))
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public static function groupedPrompts(): array
    {
        return collect(self::SAMPLE_QUESTIONS)
            ->map(fn(array $prompts) => collect($prompts)
                ->shuffle()
                ->take(2)
                ->map(fn(string $prompt) => __($prompt))
                ->values()
            )
            ->all();
    }
}
