<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Chat;
use Generator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blockchain\Domain\BlockchainFacadeInterface;
use Modules\OpenAI\Domain\Data\AiProviderDefinition;
use Modules\OpenAI\Domain\Data\ModelSelection;
use Modules\OpenAI\Domain\OpenAIFacadeInterface;
use Modules\Shared\Domain\Data\Blockchain\BlockchainData;
use Modules\Shared\Domain\Data\Blockchain\BlockData;
use Modules\Shared\Domain\Data\Chat\PromptInput;
use Modules\Shared\Domain\Enum\Chat\PromptPersona;
use Tests\TestCase;

/**
 * The chat row is written after the stream finishes, from whatever text was
 * accumulated. A provider that answers 200 but yields no usable content — a
 * quota message in the body, or a delta shape this app does not parse — used to
 * persist a chat with an empty answer and report success, so the user landed on
 * a chat page showing nothing at all.
 */
final class EmptyModelResponseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(BlockchainFacadeInterface::class, new StubBlockchainFacade());
    }

    public function test_an_empty_stream_reports_an_error_instead_of_saving_a_blank_chat(): void
    {
        $this->app->instance(OpenAIFacadeInterface::class, new StubOpenAIFacade([]));

        $body = $this->post('/stream', [
            'search' => '210000',
            'question' => 'What happened here?',
        ])->streamedContent();

        self::assertStringContainsString('"type":"error"', $body);
        self::assertSame(0, Chat::count());
    }

    public function test_a_whitespace_only_stream_is_treated_as_empty(): void
    {
        $this->app->instance(OpenAIFacadeInterface::class, new StubOpenAIFacade([' ', "\n"]));

        $body = $this->post('/stream', [
            'search' => '210000',
            'question' => 'What happened here?',
        ])->streamedContent();

        self::assertStringContainsString('"type":"error"', $body);
        self::assertSame(0, Chat::count());
    }

    public function test_a_normal_stream_still_saves_the_chat(): void
    {
        $this->app->instance(OpenAIFacadeInterface::class, new StubOpenAIFacade(['Block 210000 ', 'was mined.']));

        $body = $this->post('/stream', [
            'search' => '210000',
            'question' => 'What happened here?',
        ])->streamedContent();

        self::assertStringContainsString('"type":"done"', $body);
        self::assertSame(1, Chat::count());
    }
}

final class StubBlockchainFacade implements BlockchainFacadeInterface
{
    public function getMaxPossibleBlockHeight(): int
    {
        return 900000;
    }

    public function getCurrentBlockHeight(): int
    {
        return 900000;
    }

    public function getBlockchainData(PromptInput $input): BlockchainData
    {
        return BlockchainData::forBlock(new BlockData(hash: 'hash', height: 210000));
    }
}

final class StubOpenAIFacade implements OpenAIFacadeInterface
{
    /**
     * @param list<string> $chunks
     */
    public function __construct(private readonly array $chunks)
    {
    }

    public function generateText(
        BlockchainData $data,
        PromptInput $input,
        PromptPersona $persona,
        string $question,
        ?Chat $chat = null,
        string $additionalContext = '',
        ?ModelSelection $selection = null,
    ): string {
        return implode('', $this->chunks);
    }

    public function generateTextStreaming(
        BlockchainData $data,
        PromptInput $input,
        PromptPersona $persona,
        string $question,
        ?Chat $chat = null,
        string $additionalContext = '',
        ?ModelSelection $selection = null,
    ): Generator {
        yield from $this->chunks;
    }

    /**
     * @return list<AiProviderDefinition>
     */
    public function availableProviders(): array
    {
        return [];
    }

    public function resolveSelection(
        ?string $providerId,
        ?string $modelId,
        ?string $userApiKey = null,
    ): ?ModelSelection {
        return null;
    }
}
