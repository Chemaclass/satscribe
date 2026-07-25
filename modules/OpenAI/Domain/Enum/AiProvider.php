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
    case OpenAI = 'openai';
    case OpenRouter = 'openrouter';
    case Groq = 'groq';

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
            self::OpenAI => [
                new AiModel('gpt-4o', 'GPT-4o'),
                new AiModel('gpt-4o-mini', 'GPT-4o mini'),
                new AiModel('gpt-4.1', 'GPT-4.1'),
                new AiModel('gpt-4.1-mini', 'GPT-4.1 mini'),
            ],
            self::OpenRouter => [
                new AiModel('meta-llama/llama-3.3-70b-instruct:free', 'Llama 3.3 70B (free)', free: true),
                new AiModel('deepseek/deepseek-chat-v3-0324:free', 'DeepSeek V3 (free)', free: true),
                new AiModel('openai/gpt-4o-mini', 'GPT-4o mini'),
            ],
            self::Groq => [
                new AiModel('llama-3.3-70b-versatile', 'Llama 3.3 70B Versatile', free: true),
                new AiModel('llama-3.1-8b-instant', 'Llama 3.1 8B Instant', free: true),
            ],
        };
    }
}
