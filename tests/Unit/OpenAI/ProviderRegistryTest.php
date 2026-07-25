<?php

declare(strict_types=1);

namespace Tests\Unit\OpenAI;

use Modules\OpenAI\Application\ProviderRegistry;
use Modules\OpenAI\Domain\Data\AiProviderDefinition;
use Modules\OpenAI\Domain\Enum\AiProvider;
use Modules\OpenAI\Domain\Exception\OpenAIError;
use Modules\OpenAI\Domain\Exception\UnsupportedModelError;
use PHPUnit\Framework\TestCase;

use function count;

final class ProviderRegistryTest extends TestCase
{
    private const USER_KEY = 'sk-user-supplied-key-0123456789';

    public function test_default_selection_uses_configured_openai_model_and_endpoint(): void
    {
        $selection = $this->registry()->defaultSelection();

        self::assertSame('openai', $selection->provider->id());
        self::assertSame('gpt-4', $selection->model);
        self::assertSame('https://api.openai.com/v1/chat/completions', $selection->endpoint());
        self::assertSame('server-key', $selection->apiKey());
        self::assertFalse($selection->usesUserKey);
    }

    public function test_default_followup_selection_uses_configured_followup_model(): void
    {
        self::assertSame('gpt-4o-mini', $this->registry()->defaultFollowupSelection()->model);
    }

    /**
     * Deployments that hold a free-tier key should spend it rather than the
     * paid OpenAI one, which is the difference between a working install and
     * every chat failing on a missing or exhausted OpenAI account.
     */
    public function test_default_selection_prefers_a_configured_free_tier_provider(): void
    {
        $selection = $this->registry(groqKey: 'groq-key')->defaultSelection();

        self::assertSame('groq', $selection->provider->id());
        self::assertSame('llama-3.3-70b-versatile', $selection->model);
        self::assertSame('groq-key', $selection->apiKey());
    }

    public function test_default_selection_uses_a_free_model_of_that_provider(): void
    {
        $registry = $this->registry(openAiKey: '', openRouterKey: 'or-key');

        $selection = $registry->defaultSelection();

        self::assertSame('openrouter', $selection->provider->id());
        self::assertTrue($selection->provider->findModel($selection->model)?->free);
    }

    public function test_default_selection_stays_on_openai_when_it_is_the_only_key(): void
    {
        $selection = $this->registry(openAiKey: 'server-key')->defaultSelection();

        self::assertSame('openai', $selection->provider->id());
        self::assertSame('gpt-4', $selection->model);
    }

    /**
     * A follow-up on a free provider has no separate cheap model to fall back
     * to, so it uses that provider's free model rather than the OpenAI setting.
     */
    public function test_default_followup_selection_follows_the_free_provider(): void
    {
        $selection = $this->registry(groqKey: 'groq-key')->defaultFollowupSelection();

        self::assertSame('groq', $selection->provider->id());
        self::assertTrue($selection->provider->findModel($selection->model)?->free);
    }

    public function test_default_selection_honours_configured_base_url(): void
    {
        $registry = $this->registry(baseUrl: 'https://proxy.example.test/v1/');

        self::assertSame(
            'https://proxy.example.test/v1/chat/completions',
            $registry->defaultSelection()->endpoint(),
        );
    }

    public function test_default_selection_falls_back_to_provider_url_when_base_url_blank(): void
    {
        $registry = $this->registry(baseUrl: '');

        self::assertSame(
            'https://api.openai.com/v1/chat/completions',
            $registry->defaultSelection()->endpoint(),
        );
    }

    public function test_selection_from_blank_input_matches_the_configured_default(): void
    {
        $selection = $this->registry()->selectionFrom(null, null);

        self::assertSame('openai', $selection->provider->id());
        self::assertSame('gpt-4', $selection->model);
        self::assertFalse($selection->usesUserKey);
    }

    public function test_selection_from_known_provider_and_model_is_accepted(): void
    {
        $selection = $this->registry()->selectionFrom('groq', 'llama-3.1-8b-instant', self::USER_KEY);

        self::assertSame('groq', $selection->provider->id());
        self::assertSame('llama-3.1-8b-instant', $selection->model);
        self::assertSame('https://api.groq.com/openai/v1/chat/completions', $selection->endpoint());
        self::assertTrue($selection->usesUserKey);
    }

    public function test_selection_without_model_uses_the_providers_first_model(): void
    {
        $selection = $this->registry()->selectionFrom('openrouter', null, self::USER_KEY);

        self::assertSame(AiProvider::OpenRouter->models()[0]->id, $selection->model);
    }

    public function test_unknown_provider_is_rejected(): void
    {
        $this->expectException(UnsupportedModelError::class);

        $this->registry()->selectionFrom('evil', 'gpt-4o', self::USER_KEY);
    }

    /**
     * The registry is the only source of outbound URLs: a provider that looks
     * like a URL must be rejected rather than dialled.
     */
    public function test_provider_that_looks_like_a_url_is_rejected(): void
    {
        $this->expectException(UnsupportedModelError::class);

        $this->registry()->selectionFrom('http://169.254.169.254/latest/meta-data', 'gpt-4o', self::USER_KEY);
    }

    public function test_unknown_model_is_rejected(): void
    {
        $this->expectException(UnsupportedModelError::class);

        $this->registry()->selectionFrom('groq', 'gpt-4o', self::USER_KEY);
    }

    public function test_unknown_model_on_the_default_provider_is_rejected(): void
    {
        $this->expectException(UnsupportedModelError::class);

        $this->registry()->selectionFrom(null, 'definitely-not-a-model');
    }

    public function test_malformed_user_key_is_rejected_without_echoing_it(): void
    {
        $malformed = "sk-abc\r\nX-Injected: 1";

        try {
            $this->registry()->selectionFrom('groq', 'llama-3.1-8b-instant', $malformed);
            self::fail('Expected a malformed key to be rejected.');
        } catch (UnsupportedModelError $e) {
            self::assertStringNotContainsString('sk-abc', $e->getMessage());
            self::assertStringNotContainsString('X-Injected', $e->getMessage());
        }
    }

    public function test_provider_without_any_key_reports_the_missing_env_var(): void
    {
        $this->expectException(OpenAIError::class);
        $this->expectExceptionMessage('GROQ_API_KEY is not configured.');

        $this->registry()->selectionFrom('groq', 'llama-3.1-8b-instant');
    }

    public function test_missing_openai_key_keeps_the_historical_message(): void
    {
        $registry = $this->registry(openAiKey: '');

        $this->expectException(OpenAIError::class);
        $this->expectExceptionMessage('OPENAI_API_KEY is not configured.');

        $registry->defaultSelection();
    }

    public function test_server_key_is_used_when_the_request_brings_none(): void
    {
        $registry = $this->registry(groqKey: 'groq-server-key');
        $selection = $registry->selectionFrom('groq', 'llama-3.1-8b-instant');

        self::assertSame('groq-server-key', $selection->apiKey());
        self::assertFalse($selection->usesUserKey);
    }

    public function test_all_exposes_every_provider_with_its_server_key_state(): void
    {
        $providers = $this->registry(groqKey: 'groq-server-key')->all();

        self::assertCount(count(AiProvider::cases()), $providers);

        $byId = [];
        foreach ($providers as $provider) {
            self::assertInstanceOf(AiProviderDefinition::class, $provider);
            $byId[$provider->id()] = $provider;
        }

        self::assertFalse($byId['openai']->requiresUserKey());
        self::assertFalse($byId['groq']->requiresUserKey());
        self::assertTrue($byId['openrouter']->requiresUserKey());
        self::assertNotEmpty($byId['openrouter']->models);
    }

    public function test_find_returns_null_for_an_unknown_provider(): void
    {
        self::assertNull($this->registry()->find('anthropic'));
    }

    private function registry(
        string $baseUrl = 'https://api.openai.com/v1',
        string $openAiKey = 'server-key',
        string $groqKey = '',
        string $openRouterKey = '',
    ): ProviderRegistry {
        return new ProviderRegistry(
            openAiBaseUrl: $baseUrl,
            openAiApiKey: $openAiKey,
            openAiModel: 'gpt-4',
            openAiModelFollowup: 'gpt-4o-mini',
            openRouterApiKey: $openRouterKey,
            groqApiKey: $groqKey,
        );
    }
}
