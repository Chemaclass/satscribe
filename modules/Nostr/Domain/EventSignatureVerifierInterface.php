<?php

declare(strict_types=1);

namespace Modules\Nostr\Domain;

interface EventSignatureVerifierInterface
{
    /**
     * Verify a NIP-01 signed event. The array arrives straight from a decoded
     * request body, so no key is guaranteed — a malformed event simply fails
     * verification rather than being rejected up front.
     *
     * @param  array<array-key, mixed>  $event
     */
    public function verify(array $event): bool;
}
