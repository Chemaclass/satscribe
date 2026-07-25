<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use Modules\Shared\Domain\Chat\SentenceTrimmer;
use PHPUnit\Framework\TestCase;

final class SentenceTrimmerTest extends TestCase
{
    public function test_to_last_full_sentence_drops_trailing_partial_sentence(): void
    {
        self::assertSame(
            'This block is unusual. It has one transaction.',
            SentenceTrimmer::toLastFullSentence('This block is unusual. It has one transaction. The miner was'),
        );
    }

    public function test_to_last_full_sentence_keeps_decimals_intact(): void
    {
        self::assertSame(
            'The fee was 0.5 BTC in total.',
            SentenceTrimmer::toLastFullSentence('The fee was 0.5 BTC in total. The next bl'),
        );
    }

    public function test_to_last_full_sentence_handles_ellipsis_and_question_marks(): void
    {
        self::assertSame('Why? Because…', SentenceTrimmer::toLastFullSentence('Why? Because… and then it'));
    }

    public function test_to_last_full_sentence_strips_dangling_markdown_emphasis(): void
    {
        self::assertSame('A bold claim.', SentenceTrimmer::toLastFullSentence('A bold claim. **'));
    }

    public function test_to_last_full_sentence_returns_trimmed_text_when_no_sentence_end_found(): void
    {
        self::assertSame('no punctuation here', SentenceTrimmer::toLastFullSentence('  no punctuation here  '));
    }

    /**
     * Regression: the cut offset comes from PREG_OFFSET_CAPTURE, which is in
     * bytes. Combining it with mb_substr over-counted once per multibyte
     * character, leaking a partial word from the following sentence.
     */
    public function test_to_last_full_sentence_cuts_correctly_after_multibyte_characters(): void
    {
        self::assertSame(
            'Fee ≈ 0.0001 ₿ per byte.',
            SentenceTrimmer::toLastFullSentence('Fee ≈ 0.0001 ₿ per byte. Next sentence frag'),
        );

        self::assertSame(
            'The block reward — worth 60,000 — was paid.',
            SentenceTrimmer::toLastFullSentence('The block reward — worth 60,000 — was paid. Then more'),
        );
    }

    public function test_to_last_full_sentence_returns_valid_utf8_when_cutting_at_ellipsis(): void
    {
        $result = SentenceTrimmer::toLastFullSentence('It cost 1.5 BTC… and then a partial');

        self::assertSame('It cost 1.5 BTC…', $result);
        self::assertTrue(mb_check_encoding($result, 'UTF-8'));
    }
}
