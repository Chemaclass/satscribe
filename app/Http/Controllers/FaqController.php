<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Repositories\FaqRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class FaqController
{
    public function index(Request $request, FaqRepositoryInterface $repository): View
    {
        $search = $request->input('search', '');
        $faqs = $repository->getCollectionBySearch($search);
        $categories = $repository->getCategories($faqs);

        return view('faq', [
            'search' => $search,
            'faqs' => $faqs,
            'categories' => $categories,
        ]);
    }
}
