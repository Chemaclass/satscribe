<?php

declare(strict_types=1);

namespace Modules\NostrAuth\Domain;

interface EventSignatureVerifierInterface
{
    public function verify(array $event): bool;
}
