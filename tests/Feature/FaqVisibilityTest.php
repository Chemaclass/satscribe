<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Faq;
use Tests\TestCase;

final class FaqVisibilityTest extends TestCase
{
    public function test_faq_page_renders_when_faqs_exist(): void
    {
        config(['features.btc_price' => false]);

        Faq::factory()->create(['lang' => 'en']);

        $response = $this->get(route('faq.index'));

        $response->assertStatus(200);
        $response->assertSee('Frequently Asked Questions');
    }
}
