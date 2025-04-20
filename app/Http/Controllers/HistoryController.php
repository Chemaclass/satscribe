<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PromptResult;
use Illuminate\View\View;

final class HistoryController
{
    public function index(): View
    {
        return view('history.index', [
            'descriptions' => PromptResult::latest()->simplePaginate(5),
        ]);
    }
}
