<?php

declare(strict_types=1);

namespace Tests\Unit\OpenAI;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Translation\Translator;
use Modules\Blockchain\Domain\PriceServiceInterface;
use Modules\OpenAI\Application\OpenAIService;
use Modules\OpenAI\Application\PersonaPromptBuilder;
use Modules\OpenAI\Application\ProviderRegistry;
use Modules\OpenAI\Domain\Data\ModelSelection;
use Modules\OpenAI\Domain\Exception\OpenAIError;
use Modules\Shared\Domain\Data\Blockchain\BlockchainData;
use Modules\Shared\Domain\Data\Blockchain\BlockData;
use Modules\Shared\Domain\Data\Chat\PromptInput;
use Modules\Shared\Domain\Enum\Chat\PromptPersona;
use Modules\Shared\Domain\Enum\Chat\PromptType;
use Modules\Shared\Domain\HttpClientInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Stringable;

use function sprintf;

/**
 * The streaming path is the only one the browser uses, so provider selection
 * and key handling are pinned here.
 */
final class OpenAIServiceStreamingTest extends TestCase
{
    private const USER_KEY = 'sk-user-supplied-key-0123456789';

    /** @var list<array{level: string, message: string, context: array<string, mixed>}> */
    private array $logs = [];

    public static function setUpBeforeClass(): void
    {
        $mock = mock(Translator::class);
        $mock->shouldReceive('get')->andReturnArg(0);
        app()->instance('translator', $mock);
    }

    protected function setUp(): void
    {
        $this->logs = [];
    }

    public function test_streaming_yields_the_content_deltas(): void
    {
        $factory = $this->fakeFactory($this->sseBody(['Hel', 'lo.']));

        $chunks = iterator_to_array($this->service($factory)->generateTextStreaming(
            $this->data(),
            $this->input(),
            PromptPersona::Developer,
            'Question',
        ));

        self::assertSame(['Hel', 'lo.'], array_values($chunks));
    }

    /**
     * The generator is declared as yielding strings and callers concatenate
     * what comes out. OpenRouter and Groq are only OpenAI-compatible, so a
     * chunk whose content is not a string has to be dropped rather than
     * reaching the caller and failing as an "Array to string conversion".
     */
    public function test_streaming_drops_chunks_whose_content_is_not_a_string(): void
    {
        $body = implode("\n\n", [
            'data: ' . json_encode(['choices' => [['delta' => ['content' => 'Good.']]]], JSON_THROW_ON_ERROR),
            'data: ' . json_encode(['choices' => [['delta' => ['content' => ['nested']]]]], JSON_THROW_ON_ERROR),
            'data: ' . json_encode(['choices' => 'not-a-list'], JSON_THROW_ON_ERROR),
            'data: not json at all',
            'data: [DONE]',
        ]) . "\n\n";

        $chunks = iterator_to_array($this->service($this->fakeFactory($body))->generateTextStreaming(
            $this->data(),
            $this->input(),
            PromptPersona::Developer,
            'Question',
        ));

        self::assertSame(['Good.'], array_values($chunks));
    }

    public function test_streaming_without_a_selection_calls_the_configured_openai_default(): void
    {
        $factory = $this->fakeFactory($this->sseBody(['Hi.']));

        iterator_to_array($this->service($factory)->generateTextStreaming(
            $this->data(),
            $this->input(),
            PromptPersona::Developer,
            'Question',
        ));

        $request = $this->recordedRequest($factory);

        self::assertSame('https://api.openai.com/v1/chat/completions', $request->url());
        self::assertSame('gpt-4', $request->data()['model']);
        self::assertSame('Bearer server-key', $request->header('Authorization')[0]);
    }

    public function test_streaming_with_a_selection_calls_that_provider_with_the_user_key(): void
    {
        $factory = $this->fakeFactory($this->sseBody(['Hi.']));
        $registry = $this->registry();
        $selection = $registry->selectionFrom('groq', 'llama-3.1-8b-instant', self::USER_KEY);

        iterator_to_array($this->service($factory, $registry)->generateTextStreaming(
            $this->data(),
            $this->input(),
            PromptPersona::Developer,
            'Question',
            null,
            '',
            $selection,
        ));

        $request = $this->recordedRequest($factory);

        self::assertSame('https://api.groq.com/openai/v1/chat/completions', $request->url());
        self::assertSame('llama-3.1-8b-instant', $request->data()['model']);
        self::assertSame('Bearer ' . self::USER_KEY, $request->header('Authorization')[0]);
    }

    public function test_a_user_supplied_key_never_reaches_the_log_context(): void
    {
        $factory = $this->fakeFactory($this->sseBody(['Hi.']));
        $registry = $this->registry();
        $selection = $registry->selectionFrom('groq', 'llama-3.1-8b-instant', self::USER_KEY);

        iterator_to_array($this->service($factory, $registry)->generateTextStreaming(
            $this->data(),
            $this->input(),
            PromptPersona::Developer,
            'Question',
            null,
            '',
            $selection,
        ));

        self::assertNotEmpty($this->logs, 'Expected the streaming call to log something.');
        $this->assertLogsFreeOfTheUserKey();
    }

    public function test_a_failed_stream_throws_without_leaking_the_key(): void
    {
        $factory = new HttpFactory();
        $factory->fake(['*' => HttpFactory::response('nope', 500)]);

        $registry = $this->registry();
        $selection = $registry->selectionFrom('groq', 'llama-3.1-8b-instant', self::USER_KEY);

        try {
            iterator_to_array($this->service($factory, $registry)->generateTextStreaming(
                $this->data(),
                $this->input(),
                PromptPersona::Developer,
                'Question',
                null,
                '',
                $selection,
            ));
            self::fail('Expected a failed stream to throw.');
        } catch (OpenAIError $e) {
            self::assertStringNotContainsString(self::USER_KEY, $e->getMessage());
        }

        $this->assertLogsFreeOfTheUserKey();
    }

    /**
     * A generic "request failed" leaves an operator with a dead site and no way
     * to tell a bad key from an exhausted quota. The provider's status says
     * which, and reporting it reveals nothing about the key.
     *
     * @param non-empty-string $expected
     */
    #[DataProvider('providerFailures')]
    public function test_a_rejected_stream_reports_why(int $status, string $expected): void
    {
        $factory = new HttpFactory();
        $factory->fake(['*' => HttpFactory::response('nope', $status)]);

        $registry = $this->registry();
        $selection = $registry->selectionFrom('groq', 'llama-3.1-8b-instant', self::USER_KEY);

        try {
            iterator_to_array($this->service($factory, $registry)->generateTextStreaming(
                $this->data(),
                $this->input(),
                PromptPersona::Developer,
                'Question',
                null,
                '',
                $selection,
            ));
            self::fail('Expected a rejected stream to throw.');
        } catch (OpenAIError $e) {
            self::assertStringContainsString($expected, $e->getMessage());
            self::assertStringContainsString('Groq', $e->getMessage());
            self::assertStringNotContainsString(self::USER_KEY, $e->getMessage());
        }
    }

    /**
     * @return iterable<string, array{int, non-empty-string}>
     */
    public static function providerFailures(): iterable
    {
        yield 'bad key' => [401, 'rejected the configured API key'];
        yield 'forbidden' => [403, 'rejected the configured API key'];
        yield 'unknown model' => [404, 'does not offer the configured model'];
        yield 'quota' => [429, 'rate limited or out of quota'];
        yield 'outage' => [503, 'is having an outage'];
    }

    public function test_selection_keeps_the_key_out_of_its_serialised_forms(): void
    {
        $selection = $this->registry()->selectionFrom('groq', 'llama-3.1-8b-instant', self::USER_KEY);

        self::assertInstanceOf(ModelSelection::class, $selection);
        self::assertStringNotContainsString(self::USER_KEY, (string) json_encode($selection));
        self::assertStringNotContainsString(self::USER_KEY, print_r($selection->toLogContext(), true));
        self::assertStringNotContainsString(self::USER_KEY, print_r($selection->__debugInfo(), true));
        self::assertSame(self::USER_KEY, $selection->apiKey());
    }

    private function assertLogsFreeOfTheUserKey(): void
    {
        foreach ($this->logs as $log) {
            self::assertStringNotContainsString(self::USER_KEY, $log['message']);
            self::assertStringNotContainsString(
                self::USER_KEY,
                (string) json_encode($log['context']),
                sprintf('Key leaked into the context of "%s".', $log['message']),
            );
        }
    }

    private function service(HttpFactory $factory, ?ProviderRegistry $registry = null): OpenAIService
    {
        return new OpenAIService(
            self::createStub(HttpClientInterface::class),
            $factory,
            $this->passthroughCache(),
            $this->recordingLogger(),
            new PersonaPromptBuilder('en'),
            self::createStub(PriceServiceInterface::class),
            now(),
            $registry ?? $this->registry(),
        );
    }

    private function registry(): ProviderRegistry
    {
        return new ProviderRegistry(
            openAiBaseUrl: 'https://api.openai.com/v1',
            openAiApiKey: 'server-key',
            openAiModel: 'gpt-4',
            openAiModelFollowup: 'gpt-4o-mini',
        );
    }

    /**
     * @param list<string> $deltas
     */
    private function sseBody(array $deltas): string
    {
        $lines = array_map(
            static fn (string $delta): string => 'data: ' . json_encode([
                'choices' => [['delta' => ['content' => $delta]]],
            ], JSON_THROW_ON_ERROR),
            $deltas,
        );

        $lines[] = 'data: [DONE]';

        return implode("\n\n", $lines) . "\n\n";
    }

    private function fakeFactory(string $body): HttpFactory
    {
        $factory = new HttpFactory();
        $factory->fake(['*' => HttpFactory::response($body)]);

        return $factory;
    }

    private function recordedRequest(HttpFactory $factory): ClientRequest
    {
        $recorded = $factory->recorded();
        $first = $recorded[0] ?? null;

        if ($first === null) {
            self::fail('Expected an outbound request.');
        }

        return $first[0];
    }

    private function recordingLogger(): LoggerInterface
    {
        $logger = $this->createMock(LoggerInterface::class);

        $capture = (fn (string $level): callable => function (string|Stringable $message, array $context = []) use ($level): void {
            $this->logs[] = [
                'level' => $level,
                'message' => (string) $message,
                'context' => $context,
            ];
        });

        $logger->method('debug')->willReturnCallback($capture('debug'));
        $logger->method('info')->willReturnCallback($capture('info'));
        $logger->method('error')->willReturnCallback($capture('error'));

        return $logger;
    }

    private function passthroughCache(): CacheRepository
    {
        $cache = $this->createMock(CacheRepository::class);
        $cache->method('get')->willReturn(null);
        $cache->method('put')->willReturn(true);

        return $cache;
    }

    private function data(): BlockchainData
    {
        return BlockchainData::forBlock(new BlockData('h', height: 1, merkleRoot: 'm'));
    }

    private function input(): PromptInput
    {
        return new PromptInput(PromptType::Block, '1');
    }
}
