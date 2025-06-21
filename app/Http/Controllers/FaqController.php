<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\FaqService;
use Illuminate\Http\Request;
use Illuminate\View\View;

final readonly class FaqController
{
    public function __construct(
        private FaqService $service,
    ) {
    }

    public function index(Request $request): View
    {
        $search = $request->input('search', '');
        $data = $this->service->getFaqData($search);

        return view('faq', $data);
    }
}
