<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class FaqController extends AbstractController
{
    public function __invoke(Request $request): View
    {
        $search = $request->input('search', ''); // ⬅️ Default to empty string

        $query = Faq::query();

        if (!empty($search)) {
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $faqs = $query->orderByDesc('highlight')->orderBy('priority')->get();

        $categories = collect($faqs)->flatMap(fn($faq) => explode(',', (string) $faq->categories))->map(fn($c) => trim($c))->unique()->sort()->values();

        return $this->render('faq', [
            'faqs' => $faqs,
            'categories' => $categories,
            'search' => $search,
        ]);
    }
}
