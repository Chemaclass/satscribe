<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\View\View;

abstract class AbstractController
{
    public function render(string $view, array $data = []): View
    {
        return view($view, array_merge([
            'cronitorClientKey' => config('app.cronitorClientKey'),
        ], $data));
    }
}
