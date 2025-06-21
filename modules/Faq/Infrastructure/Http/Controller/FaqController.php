<?php

declare(strict_types=1);

namespace Modules\Faq\Infrastructure\Http\Controller;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Faq\Application\FaqService;

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
