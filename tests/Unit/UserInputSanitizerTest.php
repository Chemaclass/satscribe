<?php

namespace Tests\Unit;

use App\Services\UserInputSanitizer;
use PHPUnit\Framework\TestCase;

class UserInputSanitizerTest extends TestCase
{
    public function test_sanitizes_urls_and_flagged_words(): void
    {
        $repository = new class {
            public function getAllWords(): array
            {
                return ['badword'];
            }
        };

        $sanitizer = new UserInputSanitizer($repository);

        $input = 'Visit https://example.com for a badword example.';
        $expected = 'Visit [link removed] for a ******* example.';

        $this->assertSame($expected, $sanitizer->sanitize($input));
    }
}
