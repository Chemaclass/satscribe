<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\View\View;

final readonly class TraceUtxoPageController
{
    public function index(): View
    {
        return view('trace-utxo');
    }
}
