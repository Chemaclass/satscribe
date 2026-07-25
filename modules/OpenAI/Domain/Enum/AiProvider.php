<?php

declare(strict_types=1);

namespace Modules\OpenAI\Domain\Enum;

use Modules\OpenAI\Domain\Data\AiModel;

/**
 * The providers this app is allowed to talk to.
 *
 * All three speak the OpenAI chat-completions protocol, which is why one
 * request builder serves them all. This enum is the allowlist: a provider that
 * is not a case here can never become an outbound URL, no matter what the
 * request body says.
 */
enum AiProvider: string
{
    // Ordered as the picker shows them: the providers with a free tier come
    // first, so the cheapest way to use the app is the first thing offered.
    case Groq = 'groq';
    case OpenRouter = 'openrouter';
    case OpenAI = 'openai';

    public function label(): string
    {
        return match ($this) {
            self::OpenAI => 'OpenAI',
            self::OpenRouter => 'OpenRouter',
            self::Groq => 'Groq',
        };
    }

    public function defaultBaseUrl(): string
    {
        return match ($this) {
            self::OpenAI => 'https://api.openai.com/v1',
            self::OpenRouter => 'https://openrouter.ai/api/v1',
            self::Groq => 'https://api.groq.com/openai/v1',
        };
    }

    /**
     * Name of the env var holding the server-side key, used in error messages
     * so an operator is told which setting is missing.
     */
    public function apiKeyEnvName(): string
    {
        return match ($this) {
            self::OpenAI => 'OPENAI_API_KEY',
            self::OpenRouter => 'OPENROUTER_API_KEY',
            self::Groq => 'GROQ_API_KEY',
        };
    }

    /**
     * @return list<AiModel>
     */
    public function models(): array
    {
        // Three per provider on purpose. A long list is harder to choose from
        // than a short one, and most entries were near-duplicates: another
        // model of the same family, at the same price, on the same tier. Each
        // one kept earns its place — cheapest, best, or fastest — so the list
        // reads as a decision rather than a catalogue.
        return match ($this) {
            // Everything Groq serves is on its free tier.
            self::Groq => [
                new AiModel('llama-3.3-70b-versatile', 'Llama 3.3 70B Versatile', free: true),
                new AiModel('openai/gpt-oss-120b', 'GPT-OSS 120B', free: true),
                new AiModel('llama-3.1-8b-instant', 'Llama 3.1 8B Instant', free: true),
            ],
            // The only route to Anthropic here: Claude speaks its own protocol
            // directly, but OpenRouter fronts it with the OpenAI one this app
            // already uses. Free first, then cheapest paid.
            self::OpenRouter => [
                new AiModel('openai/gpt-oss-20b:free', 'GPT-OSS 20B', free: true),
                new AiModel('anthropic/claude-haiku-4.5', 'Claude Haiku 4.5'),
                new AiModel('anthropic/claude-sonnet-5', 'Claude Sonnet 5'),
            ],
            // No free tier here, so the cheapest-first ordering is the only
            // thing keeping an accidental click off an expensive model.
            self::OpenAI => [
                new AiModel('gpt-5-nano', 'GPT-5 nano'),
                new AiModel('gpt-4o-mini', 'GPT-4o mini'),
                new AiModel('gpt-5-mini', 'GPT-5 mini'),
            ],
        };
    }
}
