<?php

declare(strict_types=1);

namespace Modules\OpenAI\Domain\Data;

use SensitiveParameter;

/**
 * Which provider/model answers one single request, plus the key used for it.
 *
 * The key is deliberately private and marked sensitive: private properties are
 * skipped by json_encode (how Monolog normalises objects in log context), and
 * SensitiveParameter keeps it out of stack traces. A user-supplied key lives
 * for exactly one request — nothing here is ever persisted.
 */
final readonly class ModelSelection
{
    public function __construct(
        public AiProviderDefinition $provider,
        public string $model,
        #[SensitiveParameter]
        private string $apiKey,
        public bool $usesUserKey = false,
    ) {
    }

    /**
     * @return array{provider: string, model: string, byo_key: bool}
     */
    public function __debugInfo(): array
    {
        return $this->toLogContext();
    }

    public function apiKey(): string
    {
        return $this->apiKey;
    }

    public function endpoint(): string
    {
        return $this->provider->chatCompletionsUrl();
    }

    /**
     * The only shape of this object that may reach a log or an exception.
     *
     * @return array{provider: string, model: string, byo_key: bool}
     */
    public function toLogContext(): array
    {
        return [
            'provider' => $this->provider->id(),
            'model' => $this->model,
            'byo_key' => $this->usesUserKey,
        ];
    }
}
