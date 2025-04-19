<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\Http\Request;

final class FaqController
{
    public function index(Request $request)
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

        return view('faq', [
            'faqs' => $faqs,
            'categories' => $categories,
            'search' => $search,
        ]);
    }
}
