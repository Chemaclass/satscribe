<?php

declare(strict_types=1);

namespace Modules\NostrAuth\Infrastructure\Http\Controller;

use Illuminate\View\View;

final readonly class NostrPageController
{
    public function index(): View
    {
        return view('nostr');
    }
}
