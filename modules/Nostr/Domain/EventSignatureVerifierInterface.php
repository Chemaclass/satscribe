<?php

declare(strict_types=1);

namespace Modules\Nostr\Domain;

interface EventSignatureVerifierInterface
{
    public function verify(array $event): bool;
}
