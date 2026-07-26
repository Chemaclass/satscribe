<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Chat;
use App\View\Components\ModelPicker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\OpenAI\Domain\Data\AiProviderDefinition;
use Modules\OpenAI\Domain\OpenAIFacadeInterface;
use Tests\TestCase;

use function sprintf;

/**
 * The picker is built from the provider registry rather than a list written out
 * in the template, so these pin the two things that would silently rot: every
 * allowlisted model has to reach the page, and the default has to be something a
 * first-time visitor can actually run without owning an API key.
 */
final class ModelPickerRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders_every_registry_model_as_an_option(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('model-picker-select', escape: false);

        foreach ($this->providers() as $definition) {
            $response->assertSee($definition->label());

            foreach ($definition->models as $model) {
                $response->assertSee(
                    'value="' . $definition->id() . ModelPicker::CHOICE_SEPARATOR . $model->id . '"',
                    escape: false,
                );
                $response->assertSee($model->label);
            }
        }
    }

    public function test_a_chat_page_also_renders_the_picker_so_follow_ups_can_use_it(): void
    {
        $chat = Chat::create([
            'title' => 'Test Chat',
            'tracking_id' => 'owner',
            'is_public' => false,
            'is_shared' => false,
        ]);
        $chat->addUserMessage('question', ['type' => 'block', 'input' => '210000', 'persona' => 'storyteller']);
        $chat->addAssistantMessage('answer');

        $response = $this->withSession(['nostr_pubkey' => 'owner'])->get(route('chat.show', $chat));

        $response->assertStatus(200);
        $response->assertSee('model-picker-select', escape: false);
    }

    public function test_default_is_a_free_model_on_a_provider_the_server_already_has_a_key_for(): void
    {
        // Groq's first model is free, and a server key means the visitor needs none.
        config()->set('services.groq.key', 'server-side-groq-key');

        $response = $this->get('/');

        $groq = $this->provider('groq');
        $expected = $groq->id() . ModelPicker::CHOICE_SEPARATOR . $groq->models[0]->id;

        self::assertTrue($groq->models[0]->free, 'The default candidate must be a free model.');
        $response->assertSee('\u0022defaultChoice\u0022:\u0022' . $expected . '\u0022', escape: false);
    }

    public function test_default_falls_back_to_automatic_when_no_free_model_is_covered_by_a_server_key(): void
    {
        config()->set('services.openrouter.key', '');
        config()->set('services.groq.key', '');

        $response = $this->get('/');

        // Empty provider + model is exactly the pre-picker request shape.
        $response->assertSee('\u0022defaultChoice\u0022:\u0022\u0022', escape: false);
    }

    public function test_models_without_a_server_key_are_marked_as_needing_the_visitor_key(): void
    {
        config()->set('services.groq.key', '');

        $response = $this->get('/');

        $groq = $this->provider('groq');
        $choice = $groq->id() . ModelPicker::CHOICE_SEPARATOR . $groq->models[0]->id;

        $response->assertSee('\u0022' . $choice . '\u0022:true', escape: false);
        $response->assertSee(__('model_picker.badge.your_key'));
    }

    public function test_the_page_tells_the_visitor_the_key_never_leaves_their_browser(): void
    {
        $response = $this->get('/');

        $response->assertSee(__('model_picker.key.privacy'));
    }

    /**
     * The key must reach the server only through the X-Ai-Api-Key header, so it
     * must never be a named form field that a browser would post in the body.
     */
    public function test_the_key_input_is_not_a_submitted_form_field(): void
    {
        $response = $this->get('/');

        $response->assertDontSee('name="api_key"', escape: false);
        $response->assertSee('id="model-picker-key"', escape: false);
    }

    /**
     * A Claude model is only a sats purchase when this deployment holds the
     * OpenRouter key. Without one the visitor brings their own and pays their
     * own provider, so advertising a price would be wrong.
     */
    public function test_a_premium_model_is_priced_only_when_the_server_funds_it(): void
    {
        config(['services.openrouter.key' => 'openrouter-server-key']);

        $html = (string) $this->get('/')->getContent();

        self::assertStringContainsString('Claude Sonnet 5 · 500 sats', $html);
    }

    public function test_without_the_server_key_a_premium_model_asks_for_the_visitors_own(): void
    {
        config(['services.openrouter.key' => '']);

        $html = (string) $this->get('/')->getContent();

        self::assertStringContainsString('Claude Sonnet 5', $html);
        self::assertStringNotContainsString('Claude Sonnet 5 · 500 sats', $html);
        self::assertStringContainsString('needs your key', $html);
    }

    /**
     * @return list<AiProviderDefinition>
     */
    private function providers(): array
    {
        return app(OpenAIFacadeInterface::class)->availableProviders();
    }

    private function provider(string $id): AiProviderDefinition
    {
        foreach ($this->providers() as $definition) {
            if ($definition->id() === $id) {
                return $definition;
            }
        }

        self::fail(sprintf('Provider "%s" is not in the registry.', $id));
    }
}
