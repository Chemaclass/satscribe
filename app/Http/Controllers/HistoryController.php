<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PromptResult;
use Illuminate\View\View;

final class HistoryController
{
    // todo: create a new endpoint to retrieve the "show more" content and enable fast first rendering
    public function index(): View
    {
        return view('history', [
            'descriptions' => PromptResult::latest()->simplePaginate(3),
        ]);
    }
}
