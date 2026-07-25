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
        return match ($this) {
            // Everything Groq serves is on its free tier, so the whole list is
            // usable without spending anything.
            self::Groq => [
                new AiModel('llama-3.3-70b-versatile', 'Llama 3.3 70B Versatile', free: true),
                new AiModel('openai/gpt-oss-120b', 'GPT-OSS 120B', free: true),
                new AiModel('openai/gpt-oss-20b', 'GPT-OSS 20B', free: true),
                new AiModel('moonshotai/kimi-k2-instruct', 'Kimi K2', free: true),
                new AiModel('llama-3.1-8b-instant', 'Llama 3.1 8B Instant', free: true),
            ],
            // Free models first, then the cheapest paid ones.
            self::OpenRouter => [
                new AiModel('openai/gpt-oss-20b:free', 'GPT-OSS 20B', free: true),
                new AiModel('google/gemma-4-31b-it:free', 'Gemma 4 31B', free: true),
                new AiModel('nvidia/nemotron-3-super-120b-a12b:free', 'Nemotron 3 Super 120B', free: true),
                new AiModel('openai/gpt-5-nano', 'GPT-5 nano'),
                new AiModel('openai/gpt-4o-mini', 'GPT-4o mini'),
            ],
            // Cheapest first: this list has no free tier, so the ordering is
            // the only thing keeping an accidental click off an expensive model.
            self::OpenAI => [
                new AiModel('gpt-5-nano', 'GPT-5 nano'),
                new AiModel('gpt-4.1-nano', 'GPT-4.1 nano'),
                new AiModel('gpt-4o-mini', 'GPT-4o mini'),
                new AiModel('gpt-5-mini', 'GPT-5 mini'),
                new AiModel('gpt-4.1-mini', 'GPT-4.1 mini'),
                new AiModel('gpt-4o', 'GPT-4o'),
            ],
        };
    }
}
