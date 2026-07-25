<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Chat;
use Generator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blockchain\Domain\BlockchainFacadeInterface;
use Modules\OpenAI\Domain\Data\AiProviderDefinition;
use Modules\OpenAI\Domain\Data\ModelSelection;
use Modules\OpenAI\Domain\Exception\OpenAIError;
use Modules\OpenAI\Domain\OpenAIFacadeInterface;
use Modules\Shared\Domain\Data\Blockchain\BlockchainData;
use Modules\Shared\Domain\Data\Chat\PromptInput;
use Modules\Shared\Domain\Enum\Chat\PromptPersona;
use Tests\TestCase;

/**
 * With no model reachable a visitor would otherwise meet an error card. The
 * fallback answers from the on-chain data instead — but only when the operator
 * asked for it, and never pretending a model wrote it.
 */
final class OfflineFallbackStreamTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(BlockchainFacadeInterface::class, new StubBlockchainFacade());
        $this->app->instance(OpenAIFacadeInterface::class, new FailingOpenAIFacade());
    }

    public function test_it_is_off_by_default_so_an_outage_stays_visible(): void
    {
        config(['services.ai_offline_fallback' => false]);

        $body = $this->stream();

        self::assertStringContainsString('"type":"error"', $body);
        self::assertSame(0, Chat::count());
    }

    public function test_when_enabled_it_answers_from_the_chain_data(): void
    {
        config(['services.ai_offline_fallback' => true]);

        $body = $this->stream();

        self::assertStringContainsString('"type":"done"', $body);
        self::assertStringNotContainsString('"type":"error"', $body);
    }

    public function test_the_answer_admits_no_model_wrote_it(): void
    {
        config(['services.ai_offline_fallback' => true]);

        self::assertStringContainsString('No AI model was reachable', $this->stream());
    }

    public function test_the_answer_carries_the_real_block_height(): void
    {
        config(['services.ai_offline_fallback' => true]);

        self::assertStringContainsString('210,000', $this->stream());
    }

    public function test_the_chat_is_saved_so_it_can_be_revisited(): void
    {
        config(['services.ai_offline_fallback' => true]);

        $this->stream();

        self::assertSame(1, Chat::count());
    }

    /**
     * A follow-up goes through a different action, and the fallback has to
     * behave the same there or the two drift apart.
     */
    public function test_a_follow_up_message_falls_back_too(): void
    {
        config(['services.ai_offline_fallback' => true]);

        $chat = Chat::create([
            'title' => 'Test Chat',
            'tracking_id' => tracking_id(),
            'is_public' => false,
            'is_shared' => false,
        ]);
        $chat->addUserMessage('question', ['type' => 'block', 'input' => '210000', 'persona' => 'storyteller']);
        $chat->addAssistantMessage('an earlier answer');

        $body = $this->post(route('chat.add-message-stream', $chat), ['message' => 'And the fees?'])
            ->streamedContent();

        self::assertStringContainsString('"type":"done"', $body);
        self::assertStringContainsString('No AI model was reachable', $body);
    }

    public function test_a_follow_up_still_errors_when_the_fallback_is_off(): void
    {
        config(['services.ai_offline_fallback' => false]);

        $chat = Chat::create([
            'title' => 'Test Chat',
            'tracking_id' => tracking_id(),
            'is_public' => false,
            'is_shared' => false,
        ]);
        $chat->addUserMessage('question', ['type' => 'block', 'input' => '210000', 'persona' => 'storyteller']);
        $chat->addAssistantMessage('an earlier answer');

        $body = $this->post(route('chat.add-message-stream', $chat), ['message' => 'And the fees?'])
            ->streamedContent();

        self::assertStringContainsString('"type":"error"', $body);
    }

    private function stream(): string
    {
        return $this->post('/stream', [
            'search' => '210000',
            'question' => 'What happened here?',
        ])->streamedContent();
    }
}

final class FailingOpenAIFacade implements OpenAIFacadeInterface
{
    public function generateText(
        BlockchainData $data,
        PromptInput $input,
        PromptPersona $persona,
        string $question,
        ?Chat $chat = null,
        string $additionalContext = '',
        ?ModelSelection $selection = null,
    ): string {
        throw OpenAIError::providerRejected('OpenAI', 429);
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
        // A real provider failure surfaces when the stream is first read, not
        // when the generator is created, so this fails at the same moment.
        yield from [];

        throw OpenAIError::providerRejected('OpenAI', 429);
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
