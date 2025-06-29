<?php

declare(strict_types=1);

namespace Modules\NostrAuth\Application;

use Modules\NostrAuth\Domain\EventSignatureVerifierInterface;
use swentel\nostr\Event\Event;

final class EventSignatureVerifier implements EventSignatureVerifierInterface
{
    public function verify(array $event): bool
    {
        return (new Event())->verify(
            json_encode($event, JSON_THROW_ON_ERROR),
        );
    }
}
