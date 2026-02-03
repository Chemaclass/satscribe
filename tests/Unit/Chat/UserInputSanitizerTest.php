<?php

declare(strict_types=1);

namespace Tests\Unit\Chat;

use Modules\Chat\Domain\Data\UserInputSanitizer;
use Modules\Chat\Domain\Repository\FlaggedWordRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class UserInputSanitizerTest extends TestCase
{
    public function test_sanitizes_urls_and_flagged_words(): void
    {
        $repository = $this->createRepository(['badword']);

        $sanitizer = new UserInputSanitizer($repository);

        $input = 'Visit https://example.com for a badword example.';
        $expected = 'Visit [link removed] for a ******* example.';

        $this->assertSame($expected, $sanitizer->sanitize($input));
    }

    public function test_sanitizes_https_urls(): void
    {
        $sanitizer = new UserInputSanitizer($this->createRepository([]));

        $input = 'Check https://example.com/path?query=value for info';
        $expected = 'Check [link removed] for info';

        $this->assertSame($expected, $sanitizer->sanitize($input));
    }

    public function test_sanitizes_http_urls(): void
    {
        $sanitizer = new UserInputSanitizer($this->createRepository([]));

        $input = 'Check http://example.com for info';
        $expected = 'Check [link removed] for info';

        $this->assertSame($expected, $sanitizer->sanitize($input));
    }

    public function test_sanitizes_www_urls(): void
    {
        $sanitizer = new UserInputSanitizer($this->createRepository([]));

        $input = 'Visit www.example.com today';
        $expected = 'Visit [link removed] today';

        $this->assertSame($expected, $sanitizer->sanitize($input));
    }

    public function test_sanitizes_multiple_urls(): void
    {
        $sanitizer = new UserInputSanitizer($this->createRepository([]));

        $input = 'Check https://a.com and http://b.com';
        $expected = 'Check [link removed] and [link removed]';

        $this->assertSame($expected, $sanitizer->sanitize($input));
    }

    public function test_sanitizes_flagged_words_case_insensitive(): void
    {
        $sanitizer = new UserInputSanitizer($this->createRepository(['spam']));

        $input = 'This is SPAM and also Spam';
        $expected = 'This is **** and also ****';

        $this->assertSame($expected, $sanitizer->sanitize($input));
    }

    public function test_sanitizes_multiple_flagged_words(): void
    {
        $sanitizer = new UserInputSanitizer($this->createRepository(['foo', 'bar']));

        $input = 'foo and bar are flagged';
        $expected = '*** and *** are flagged';

        $this->assertSame($expected, $sanitizer->sanitize($input));
    }

    public function test_preserves_clean_text(): void
    {
        $sanitizer = new UserInputSanitizer($this->createRepository(['badword']));

        $input = 'This is perfectly clean text';

        $this->assertSame($input, $sanitizer->sanitize($input));
    }

    public function test_handles_empty_string(): void
    {
        $sanitizer = new UserInputSanitizer($this->createRepository(['badword']));

        $this->assertSame('', $sanitizer->sanitize(''));
    }

    public function test_handles_no_flagged_words(): void
    {
        $sanitizer = new UserInputSanitizer($this->createRepository([]));

        $input = 'Any text here';

        $this->assertSame($input, $sanitizer->sanitize($input));
    }

    private function createRepository(array $words): FlaggedWordRepositoryInterface
    {
        return new class($words) implements FlaggedWordRepositoryInterface {
            public function __construct(private array $words)
            {
            }

            public function getAllWords(): array
            {
                return $this->words;
            }
        };
    }
}
