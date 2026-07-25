<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\OpenAI\Domain\Data\AiModel;
use Modules\OpenAI\Domain\Data\AiProviderDefinition;
use Modules\OpenAI\Domain\OpenAIFacadeInterface;

/**
 * Feeds the model picker from the provider registry. The list is never written
 * out in a template or a JS file: it comes from `availableProviders()` on every
 * render, so adding a model to the registry is enough to make it selectable.
 */
final class ModelPicker extends Component
{
    /**
     * Joins a provider id and a model id into one `<option>` value, so the
     * whole selection is a single string that round-trips through localStorage.
     */
    public const CHOICE_SEPARATOR = '::';

    /** @var list<AiProviderDefinition> */
    private readonly array $providers;

    public function __construct(OpenAIFacadeInterface $openAIFacade)
    {
        $this->providers = $openAIFacade->availableProviders();
    }

    public function render(): View
    {
        return view('components.model-picker', [
            'providers' => array_map(
                static fn (AiProviderDefinition $definition): array => $definition->toArray(),
                $this->providers,
            ),
            'defaultChoice' => $this->defaultChoice(),
            'requiresKeyByChoice' => $this->requiresKeyByChoice(),
        ]);
    }

    /**
     * A first-time visitor should be able to ask something without owning an
     * API key, so the pre-selected model is a free one on a provider this
     * deployment already holds a key for. When there is no such model the
     * picker starts on "automatic", which sends no provider at all and leaves
     * the request behaving exactly as it did before model selection existed.
     */
    private function defaultChoice(): string
    {
        foreach ($this->providers as $definition) {
            if ($definition->requiresUserKey()) {
                continue;
            }

            foreach ($definition->models as $model) {
                if ($model->free) {
                    return $this->choiceValue($definition, $model);
                }
            }
        }

        return '';
    }

    /**
     * Which choices the browser must supply a key for — the picker warns before
     * submitting instead of letting the request come back as an error.
     *
     * @return array<string, bool>
     */
    private function requiresKeyByChoice(): array
    {
        $map = [];

        foreach ($this->providers as $definition) {
            foreach ($definition->models as $model) {
                $map[$this->choiceValue($definition, $model)] = $definition->requiresUserKey();
            }
        }

        return $map;
    }

    private function choiceValue(AiProviderDefinition $definition, AiModel $model): string
    {
        return $definition->id() . self::CHOICE_SEPARATOR . $model->id;
    }
}
