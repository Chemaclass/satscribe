<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Chat;

use function strlen;

/**
 * Cuts a truncated LLM response back to its last complete sentence.
 *
 * Both the streaming and non-streaming paths need this, in two different
 * modules. It lived as three near-copies that had already drifted apart on
 * the punctuation they recognised.
 */
final class SentenceTrimmer
{
    public static function toLastFullSentence(string $text): string
    {
        $text = trim($text);

        // The lookahead keeps decimals ("0.5 BTC") and abbreviations from
        // being mistaken for a sentence end.
        preg_match_all('/[.?!…](?=\s|$)/u', $text, $matches, PREG_OFFSET_CAPTURE);

        if ($matches[0] !== []) {
            $last = end($matches[0]);
            // PREG_OFFSET_CAPTURE reports BYTE offsets even under /u, so the cut
            // must stay in bytes. Mixing it with mb_substr over-counted by one
            // position per multibyte character earlier in the text, leaking a
            // partial word from the next sentence ("… per byte. Nex").
            $clean = substr($text, 0, $last[1] + strlen($last[0]));

            return trim((string) preg_replace('/(\*\*|\*|_|\-)+$/u', '', $clean));
        }

        return $text;
    }
}
