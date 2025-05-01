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
        ],
        'block' => [
            "Which pool mined this block?",
            "How many transactions are inside?",
            "How big is this block and how fast was it mined?",
            "Are there any Ordinals or inscriptions?",
        ],
        'both' => [
            "Explain this like I'm five.",
            "What's unusual or interesting here?",
            "Could this be linked to an exchange or service?",
            "Write a short, simple summary.",
        ],
    ];

    public static function rand(): string
    {
        return collect(self::SAMPLE_QUESTIONS)->flatten()->random();
    }

    /**
     * @return list<string>
     */
    public static function groupedPrompts(): array
    {
        return self::SAMPLE_QUESTIONS;
    }
}
