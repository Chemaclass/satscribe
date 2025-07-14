<?php

declare(strict_types=1);

namespace Modules\NostrAuth;

use Illuminate\Support\ServiceProvider;
use Modules\NostrAuth\Application\EventSignatureVerifier;
use Modules\NostrAuth\Domain\EventSignatureVerifierInterface;

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
