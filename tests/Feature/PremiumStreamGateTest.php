<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blockchain\Domain\BlockchainFacadeInterface;
use Modules\OpenAI\Domain\OpenAIFacadeInterface;
use Modules\Payment\Domain\PremiumCreditsInterface;
use Tests\TestCase;

/**
 * End to end through the route: a premium model must not reach the provider
 * without credit, and the refusal has to arrive as a stream event the chat UI
 * can render rather than a dead connection.
 */
final class PremiumStreamGateTest extends TestCase
{
    use RefreshDatabase;

    private const NPUB = 'a1b2c3d4e5f60718293a4b5c6d7e8f90a1b2c3d4e5f60718293a4b5c6d7e8f90';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.openrouter.key' => 'openrouter-server-key']);

        $this->app->instance(BlockchainFacadeInterface::class, new StubBlockchainFacade());
    }

    public function test_a_premium_model_without_a_login_never_reaches_the_provider(): void
    {
        $openAi = $this->spyOpenAi();

        $body = $this->streamWithClaude();

        self::assertStringContainsString('"type":"error"', $body);
        self::assertStringContainsString('Nostr login', $body);
        self::assertSame(0, $openAi->calls);
    }

    public function test_a_premium_model_without_credit_never_reaches_the_provider(): void
    {
        $openAi = $this->spyOpenAi();

        $body = $this->streamWithClaude(npub: self::NPUB);

        self::assertStringContainsString('"type":"error"', $body);
        self::assertStringContainsString('no premium messages left', $body);
        self::assertSame(0, $openAi->calls);
    }

    public function test_a_premium_model_with_credit_reaches_the_provider_and_spends_one(): void
    {
        $openAi = $this->spyOpenAi();
        app(PremiumCreditsInterface::class)->grantPack(self::NPUB, 'hash-1', 3);

        $body = $this->streamWithClaude(npub: self::NPUB);

        self::assertStringContainsString('"type":"done"', $body);
        self::assertSame(1, $openAi->calls);
        self::assertSame(2, app(PremiumCreditsInterface::class)->balanceFor(self::NPUB));
    }

    /**
     * A refused request must not be charged for, or a visitor without credit
     * would pay for the refusal itself.
     */
    public function test_a_refusal_does_not_spend_credit(): void
    {
        $this->spyOpenAi();
        app(PremiumCreditsInterface::class)->grantPack(self::NPUB, 'hash-1', 1);

        $this->streamWithClaude(npub: self::NPUB);
        $this->streamWithClaude(npub: self::NPUB);

        self::assertSame(0, app(PremiumCreditsInterface::class)->balanceFor(self::NPUB));
    }

    private function streamWithClaude(?string $npub = null): string
    {
        $test = $npub === null ? $this : $this->withSession(['nostr_pubkey' => $npub]);

        return $test->post('/stream', [
            'search' => '210000',
            'question' => 'What happened here?',
            'provider' => 'openrouter',
            'model' => 'anthropic/claude-sonnet-5',
        ])->streamedContent();
    }

    private function spyOpenAi(): StubOpenAIFacade
    {
        $openAi = new StubOpenAIFacade(['An answer.']);

        $this->app->instance(OpenAIFacadeInterface::class, $openAi);

        return $openAi;
    }
}
