<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ?lang= is written straight into the session and then handed to
 * app()->setLocale(), which Laravel turns into a translation file path. Only
 * locales the app actually ships may get that far.
 */
final class SetLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_supported_locale_is_applied(): void
    {
        $this->get('/?lang=de');

        self::assertSame('de', app()->getLocale());
    }

    public function test_an_unsupported_locale_falls_back(): void
    {
        $this->get('/?lang=fr');

        self::assertSame('en', app()->getLocale());
    }

    public function test_a_traversal_attempt_is_rejected(): void
    {
        $this->get('/?lang=' . urlencode('../../../../etc/passwd'));

        self::assertSame('en', app()->getLocale());
    }

    public function test_a_non_string_locale_is_rejected(): void
    {
        $this->withSession(['app_locale' => ['de']])->get('/');

        self::assertSame('en', app()->getLocale());
    }
}
