<?php

declare(strict_types=1);

namespace Modules\Nostr;

use Illuminate\Support\ServiceProvider;
use Modules\Nostr\Application\EventSignatureVerifier;
use Modules\Nostr\Domain\EventSignatureVerifierInterface;

final class NostrAuthServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public $singletons = [
        EventSignatureVerifierInterface::class => EventSignatureVerifier::class,
    ];

    /** @var array<class-string, class-string> */
    public $bindings = [];

    public function register(): void
    {
    }
}
