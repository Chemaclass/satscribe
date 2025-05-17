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
        ],
        'both' => [
            "Explain this like I'm five.",
            "What's unusual or interesting here?",
            "Write a short, simple summary.",
            "Can you identify any trends or anomalies?",
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
        $blockPrompts = collect(self::SAMPLE_QUESTIONS['block'])->shuffle()->take(2);
        $bothPrompts = collect(self::SAMPLE_QUESTIONS['both'])->shuffle()->take(1);

        return $blockPrompts->merge($bothPrompts)->shuffle()->values()->all();
    }

    /**
     * @return list<string>
     */
    public static function forTx(): array
    {
        $blockPrompts = collect(self::SAMPLE_QUESTIONS['transaction'])->shuffle()->take(2);
        $bothPrompts = collect(self::SAMPLE_QUESTIONS['both'])->shuffle()->take(1);

        return $blockPrompts->merge($bothPrompts)->shuffle()->values()->all();
    }

    /**
     * @return list<string>
     */
    public static function groupedPrompts(): array
    {
        return collect(self::SAMPLE_QUESTIONS)
            ->map(fn(array $prompts) => collect($prompts)->shuffle()->take(2))
            ->all();
    }
}
