<?php

declare(strict_types=1);

namespace Modules\OpenAI\Application;

use Modules\OpenAI\Domain\OfflineNarratorInterface;
use Modules\Shared\Domain\Data\Blockchain\BlockchainData;
use Modules\Shared\Domain\Enum\Chat\PromptPersona;

/**
 * Whether a provider failure should produce a data-only summary instead of an
 * error, and the narrator that writes it.
 *
 * Off by default: silently answering when the model is unreachable hides an
 * outage from the operator, so turning it on is a deliberate choice for a demo
 * or free deployment.
 */
final readonly class OfflineFallback
{
    public function __construct(
        private OfflineNarratorInterface $narrator,
        private bool $enabled = false,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function narrate(BlockchainData $data, PromptPersona $persona): string
    {
        return $this->narrator->narrate($data, $persona);
    }
}
