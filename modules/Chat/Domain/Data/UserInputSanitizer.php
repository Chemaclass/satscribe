<?php

declare(strict_types=1);

namespace Modules\Chat\Domain\Data;

use Modules\Chat\Domain\Repository\FlaggedWordRepositoryInterface;

use function strlen;

final readonly class UserInputSanitizer
{
    public function __construct(
        private FlaggedWordRepositoryInterface $flaggedWordRepository,
    ) {
    }

    public function sanitize(string $input): string
    {
        $sanitized = $this->removeUrls($input);

        foreach ($this->flaggedWordRepository->getAllWords() as $word) {
            $pattern = '/\b' . preg_quote($word, '/') . '\b/i';
            $sanitized = preg_replace(
                $pattern,
                str_repeat('*', strlen($word)),
                (string) $sanitized,
            );
        }

        return $sanitized;
    }

    private function removeUrls(string $text): string
    {
        // Matches common URL patterns (http, https, www, etc.)
        $urlPattern = '/\b(?:https?:\/\/|www\.)[^\s<>"\']+/i';

        return preg_replace($urlPattern, '[link removed]', $text);
    }
}
