<?php

declare(strict_types=1);

namespace Modules\OpenAI\Domain\Data;

use LogicException;

use Modules\OpenAI\Domain\Enum\AiProvider;

use function sprintf;

/**
 * A provider as the app is actually configured to use it: the allowlisted
 * base URL, the models we accept, and whether the server already holds a key.
 *
 * Only the registry builds these, which is what keeps a request-supplied
 * string from ever becoming an outbound URL.
 */
final readonly class AiProviderDefinition
{
    /**
     * @param list<AiModel> $models
     */
    public function __construct(
        public AiProvider $provider,
        public string $baseUrl,
        public array $models,
        public bool $hasServerKey,
    ) {
    }

    public function id(): string
    {
        return $this->provider->value;
    }

    public function label(): string
    {
        return $this->provider->label();
    }

    public function chatCompletionsUrl(): string
    {
        return rtrim($this->baseUrl, '/') . '/chat/completions';
    }

    public function supports(string $modelId): bool
    {
        return $this->findModel($modelId) instanceof AiModel;
    }

    public function findModel(string $modelId): ?AiModel
    {
        foreach ($this->models as $model) {
            if ($model->id === $modelId) {
                return $model;
            }
        }

        return null;
    }

    public function defaultModel(): AiModel
    {
        $first = $this->models[0] ?? null;

        if (!$first instanceof AiModel) {
            throw new LogicException(sprintf('Provider "%s" has no models.', $this->id()));
        }

        return $first;
    }

    /**
     * The model to reach for when nothing was asked for: the cheapest one the
     * provider offers, falling back to its first if none is free.
     */
    public function defaultFreeModel(): AiModel
    {
        foreach ($this->models as $model) {
            if ($model->free) {
                return $model;
            }
        }

        return $this->defaultModel();
    }

    /**
     * True when the caller must bring their own key because the server has none.
     */
    public function requiresUserKey(): bool
    {
        return !$this->hasServerKey;
    }

    /**
     * @return array{
     *     id: string,
     *     label: string,
     *     requires_user_key: bool,
     *     models: list<array{id: string, label: string, free: bool}>,
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id(),
            'label' => $this->label(),
            'requires_user_key' => $this->requiresUserKey(),
            'models' => array_map(static fn (AiModel $model): array => $model->toArray(), $this->models),
        ];
    }
}
