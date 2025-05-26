<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Faq;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FaqVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_faq_page_returns_404_when_no_faqs_for_locale(): void
    {
        app()->setLocale('fr');

        $response = $this->get(route('faq.index'));

        $response->assertStatus(404);
    }

    public function test_faq_page_renders_when_faqs_exist(): void
    {
        Faq::factory()->create(['lang' => 'en']);

        $response = $this->get(route('faq.index'));

        $response->assertStatus(200);
        $response->assertSee('Frequently Asked Questions');
    }
}
