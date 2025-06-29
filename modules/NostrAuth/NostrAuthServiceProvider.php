<?php

declare(strict_types=1);

namespace Modules\NostrAuth;

use Illuminate\Support\ServiceProvider;
use Modules\NostrAuth\Application\EventSignatureVerifier;

final class NostrAuthServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public $singletons = [
        EventSignatureVerifier::class => EventSignatureVerifier::class,
    ];

    /** @var array<class-string, class-string> */
    public $bindings = [];

    public function register(): void
    {
    }
}
