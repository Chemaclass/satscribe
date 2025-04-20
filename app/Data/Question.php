<?php
declare(strict_types=1);

namespace App\Data;

final class Question
{
    public const SAMPLE_QUESTIONS = [
        // Transaction-focused
        'How many inputs and outputs are there?',
        'What is the transaction fee?',
        'What is the fee rate (sat/vB)?',
        'Is this transaction using Taproot?',
        'Is this a SegWit transaction?',
        'What is the total value transferred?',
        'What is the largest output value?',
        'Are any outputs non-standard?',
        'Are any of the addresses multisig?',
        'Is this a coinbase transaction?',
        'Was the fee considered high at the time?',
        'What type of script does this transaction use?',
        'What’s the likely purpose of this transaction?',
        'Is this part of a CoinJoin?',
        'Is this a self-transfer between wallets?',
        'Does this transaction look like a batch payment?',
        'Is this transaction economically relevant?',
        'How old are the inputs in this transaction?',

        // Block-focused
        'Which mining pool mined this block?',
        'How many transactions are in this block?',
        'What is the total block reward?',
        'What is the size of this block?',
        'How long did it take to mine this block?',
        'Are there any large transactions in this block?',
        'Are there any Ordinals or inscriptions here?',
        'What was the difficulty for this block?',
        'Was this block mined quickly or slowly?',
        'What is the average fee per transaction in this block?',
        'Are there any unusual patterns in this block?',
        'How much total value was transferred in this block?',

        // General & Creative
        'Explain this block in simple terms.',
        'Is there anything interesting or unusual about this transaction?',
        'Write a short explanation for beginners.',
        'What stands out in this block’s activity?',
        'Could this transaction be related to an exchange?',
        'Is this a common pattern seen in Bitcoin usage?',
    ];

    public static function rand(): string
    {
        return self::SAMPLE_QUESTIONS[array_rand(self::SAMPLE_QUESTIONS)];
    }
}
