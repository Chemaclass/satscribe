<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\OpenAI\Domain\OpenAIFacadeInterface;
use Modules\Payment\Domain\Exception\PremiumCreditRequired;
use Modules\Payment\Domain\PremiumAccessInterface;
use Modules\Payment\Domain\PremiumCreditsInterface;
use Tests\TestCase;

/**
 * The gate between a visitor and a model this deployment pays for. Everything
 * it lets through costs the operator real money, so each way past it is pinned.
 */
final class PremiumAccessTest extends TestCase
{
    use RefreshDatabase;

    private const NPUB = 'a1b2c3d4e5f60718293a4b5c6d7e8f90a1b2c3d4e5f60718293a4b5c6d7e8f90';

    protected function setUp(): void
    {
        parent::setUp();

        // The gate only makes sense on a deployment that funds these providers,
        // which is exactly when a premium model is served with its own key.
        config([
            'services.groq.key' => 'groq-server-key',
            'services.openrouter.key' => 'openrouter-server-key',
        ]);
    }

    public function test_a_free_model_needs_no_credit_and_no_login(): void
    {
        $this->access()->authorise($this->selection('groq', 'llama-3.3-70b-versatile'), null);

        self::assertSame(0, $this->credits()->balanceFor(self::NPUB));
    }

    public function test_the_automatic_default_needs_no_credit(): void
    {
        $this->access()->authorise(null, null);

        self::assertSame(0, $this->credits()->balanceFor(self::NPUB));
    }

    public function test_a_premium_model_without_a_login_is_refused(): void
    {
        $this->expectException(PremiumCreditRequired::class);
        $this->expectExceptionMessage('Nostr login');

        $this->access()->authorise($this->selection('openrouter', 'anthropic/claude-sonnet-5'), null);
    }

    public function test_a_premium_model_without_credit_is_refused(): void
    {
        $this->expectException(PremiumCreditRequired::class);
        $this->expectExceptionMessage('no premium messages left');

        $this->access()->authorise($this->selection('openrouter', 'anthropic/claude-sonnet-5'), self::NPUB);
    }

    public function test_a_premium_model_with_credit_spends_exactly_one(): void
    {
        $this->credits()->grantPack(self::NPUB, 'hash-1', 3);

        $this->access()->authorise($this->selection('openrouter', 'anthropic/claude-sonnet-5'), self::NPUB);

        self::assertSame(2, $this->credits()->balanceFor(self::NPUB));
    }

    /**
     * A visitor paying their own provider is not spending the operator's money,
     * so the gate must not charge them for it.
     */
    public function test_a_premium_model_on_the_visitors_own_key_is_not_charged(): void
    {
        $this->credits()->grantPack(self::NPUB, 'hash-1', 3);

        $selection = $this->selection(
            'openrouter',
            'anthropic/claude-sonnet-5',
            'sk-user-supplied-key-0123456789',
        );

        $this->access()->authorise($selection, self::NPUB);

        self::assertSame(3, $this->credits()->balanceFor(self::NPUB));
    }

    private function access(): PremiumAccessInterface
    {
        return app(PremiumAccessInterface::class);
    }

    private function credits(): PremiumCreditsInterface
    {
        return app(PremiumCreditsInterface::class);
    }

    private function selection(string $provider, string $model, ?string $userKey = null): \Modules\OpenAI\Domain\Data\ModelSelection
    {
        $selection = app(OpenAIFacadeInterface::class)->resolveSelection($provider, $model, $userKey);

        self::assertNotNull($selection);

        return $selection;
    }
}
