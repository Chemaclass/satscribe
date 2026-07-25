<?php

declare(strict_types=1);

namespace Tests\Unit\OpenAI;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Translation\Translator;
use Modules\Blockchain\Domain\PriceServiceInterface;
use Modules\OpenAI\Application\OpenAIFacade;
use Modules\OpenAI\Application\OpenAIService;
use Modules\OpenAI\Application\PersonaPromptBuilder;
use Modules\OpenAI\Application\ProviderRegistry;
use Modules\OpenAI\Domain\Data\AiProviderDefinition;
use Modules\OpenAI\Domain\Exception\UnsupportedModelError;
use Modules\Shared\Domain\Data\Blockchain\BlockchainData;
use Modules\Shared\Domain\Data\Blockchain\BlockData;
use Modules\Shared\Domain\Data\Chat\PromptInput;
use Modules\Shared\Domain\Enum\Chat\PromptPersona;
use Modules\Shared\Domain\Enum\Chat\PromptType;
use Modules\Shared\Domain\HttpClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class OpenAIFacadeTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        $mock = mock(Translator::class);
        $mock->shouldReceive('get')->andReturnArg(0);
        app()->instance('translator', $mock);
    }

    public function test_generate_text_delegates_to_service(): void
    {
        $response = $this->createMock(Response::class);
        $response->method('failed')->willReturn(false);
        $response->method('json')->willReturnCallback(static fn (string $key) => match ($key) {
            'choices.0.message.content' => 'Sentence 1. Sentence 2',
            'error.message' => null,
            default => null,
        });

        $pending = $this->createMock(PendingRequest::class);
        $pending->expects($this->once())
            ->method('post')
            ->willReturn($response);

        $http = $this->createMock(HttpClientInterface::class);
        $http->expects($this->once())
            ->method('withToken')
            ->willReturn($pending);

        $logger = self::createStub(LoggerInterface::class);

        $block = new BlockData('h', height: 1, merkleRoot: 'm');
        $data = BlockchainData::forBlock($block);
        $input = new PromptInput(PromptType::Block, '1');

        $service = new OpenAIService(
            $http,
            self::createStub(HttpFactory::class),
            $this->createPassthroughCache(),
            $logger,
            new PersonaPromptBuilder('en'),
            self::createStub(PriceServiceInterface::class),
            now(),
            $registry = new ProviderRegistry(
                openAiBaseUrl: 'https://api.openai.com/v1',
                openAiApiKey: 'key',
                openAiModel: 'model',
                openAiModelFollowup: 'model-mini',
            ),
        );
        $facade = new OpenAIFacade($service, $registry);

        $result = $facade->generateText($data, $input, PromptPersona::Educator, 'Question');

        $this->assertSame('Sentence 1.', $result);
    }

    public function test_resolve_selection_returns_null_when_the_request_expressed_no_preference(): void
    {
        self::assertNull($this->facade()->resolveSelection(null, null, null));
        self::assertNull($this->facade()->resolveSelection('', '  ', ''));
    }

    public function test_resolve_selection_validates_against_the_registry(): void
    {
        $selection = $this->facade()->resolveSelection('groq', 'llama-3.1-8b-instant', 'sk-user-key-0123456789');

        self::assertNotNull($selection);
        self::assertSame('groq', $selection->provider->id());
        self::assertSame('https://api.groq.com/openai/v1/chat/completions', $selection->endpoint());
    }

    public function test_resolve_selection_rejects_an_unlisted_provider(): void
    {
        $this->expectException(UnsupportedModelError::class);

        $this->facade()->resolveSelection('https://evil.test', 'gpt-4o', 'sk-user-key-0123456789');
    }

    /**
     * The order is what the picker renders, so the free-tier providers come
     * first and the paid-only one last.
     */
    public function test_available_providers_exposes_the_allowlist_free_tier_first(): void
    {
        $ids = array_map(
            static fn (AiProviderDefinition $provider): string => $provider->id(),
            $this->facade()->availableProviders(),
        );

        self::assertSame(['groq', 'openrouter', 'openai'], $ids);
    }

    /**
     * A visitor scanning the list should meet the cheapest option first, and
     * never have a paid model sitting above a free one.
     */
    public function test_every_provider_lists_its_free_models_first(): void
    {
        foreach ($this->facade()->availableProviders() as $provider) {
            $free = array_map(
                static fn ($model): bool => $model->free,
                $provider->models,
            );

            $sorted = $free;
            rsort($sorted);

            self::assertSame($sorted, $free, "{$provider->id()} lists a paid model above a free one.");
        }
    }

    private function facade(): OpenAIFacade
    {
        $registry = new ProviderRegistry(
            openAiBaseUrl: 'https://api.openai.com/v1',
            openAiApiKey: 'key',
            openAiModel: 'model',
            openAiModelFollowup: 'model-mini',
        );

        $service = new OpenAIService(
            self::createStub(HttpClientInterface::class),
            self::createStub(HttpFactory::class),
            $this->createPassthroughCache(),
            self::createStub(LoggerInterface::class),
            new PersonaPromptBuilder('en'),
            self::createStub(PriceServiceInterface::class),
            now(),
            $registry,
        );

        return new OpenAIFacade($service, $registry);
    }

    private function createPassthroughCache(): CacheRepository
    {
        $cache = $this->createMock(CacheRepository::class);
        $cache->method('get')->willReturn(null);
        $cache->method('put')->willReturn(true);

        return $cache;
    }
}
