<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\FlaggedWordRepository;

final readonly class UserInputSanitizer
{
    public function __construct(
        private FlaggedWordRepository $flaggedWordRepository,
    ) {
    }

    public function sanitize(string $input): string
    {
        $sanitized = $input;

        foreach ($this->flaggedWordRepository->getAllWords() as $word) {
            $pattern = '/\b'.preg_quote($word, '/').'\b/i';
            $sanitized = preg_replace($pattern, str_repeat('*', strlen($word)), (string) $sanitized);
        }

        return $sanitized;
    }
}
