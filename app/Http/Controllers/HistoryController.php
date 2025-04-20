<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PromptResult;
use Illuminate\View\View;

final class HistoryController extends AbstractController
{
    public function __invoke(): View
    {
        return $this->render('prompt-result/history', [
            'descriptions' => PromptResult::latest()->simplePaginate(5),
        ]);
    }
}
